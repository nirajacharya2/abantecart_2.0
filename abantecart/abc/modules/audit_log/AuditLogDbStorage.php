<?php

namespace abc\modules\audit_log;

use abc\core\engine\Registry;
use abc\core\lib\ADB;
use abc\core\lib\contracts\AuditLogStorageInterface;
use abc\models\QueryBuilder;
use abc\models\system\AuditEvent;
use abc\models\system\AuditEventDescription;
use abc\models\system\AuditModel;
use abc\models\system\AuditUser;

/**
 * Class AuditLogDbStorage
 *
 * @package abc\modules\audit_log
 */
class AuditLogDbStorage implements AuditLogStorageInterface
{
    private $db;
    private $data;

    /**
     * AuditLogDbStorage constructor.
     */
    public function __construct()
    {
        /** @var ADB db */
        $this->db = Registry::db();
    }

    /**
     * Method for write Audit log data to storage (DB, ElasticSearch, etc)
     *
     * @param array $data
     *
     */
    public function write(array $data)
    {
        $this->db->beginTransaction();

        $auditableModelId = AuditEvent::getModelIdByName($data['entity']['name']);
        $data['entity']['model_id'] = $auditableModelId;

        $auditUserId = AuditEvent::getUserId(
            AuditUser::getUserTypeId($data['actor']['group']),
            (int)$data['actor']['id'],
            $data['actor']['name']
        );

        $eventId = AuditEvent::getEventIdByParams(
            [
                'audit_user_id'           => $auditUserId,
                'request_id'              => $data['id'],
                'event_type_id'           => AuditEvent::EVENT_NAMES[$data['entity']['group']] ?: 1,
                'main_auditable_model_id' => $data['entity']['model_id'],
                'main_auditable_id'       => $data['entity']['id'],
            ]
        );

        if ($eventId) {
            $eventDescriptions = [];
            foreach ($data['changes'] as $change) {
                if (!$change['groupName']) {
                    $change['groupName'] = $change['name'];
                }

                $modelId = AuditEvent::getModelIdByName($change['groupName']);
                $change['model_id'] = $modelId;
                $eventDescriptions[] = [
                    'auditable_model_id' => $change['model_id'],
                    'auditable_id'       => $change['groupId'] ?: 0,
                    'field_name'         => $change['name'],
                    'old_value'          => $change['oldValue'],
                    'new_value'          => $change['newValue'],
                    'audit_event_id'     => $eventId,
                ];

            }
            if ($eventDescriptions) {
                $this->db->table('audit_event_descriptions')->insert($eventDescriptions);
            }
        }
        $this->db->commit();

    }

    /**
     * Method for get Audit log events from storage (DB, ElasticSearch, etc)
     *
     * @param array $request
     *
     * @return mixed
     */
    public function getEvents(array $request)
    {
        /**
         * @var array $filter
         * @var string $date_from
         * @var string $date_to
         * @var string $user_name
         * @var string $page
         * @var string $rowsPerPage
         * @var string $sortBy
         * @var string $descending
         * @var array  $events
         */
        extract($request, EXTR_OVERWRITE);
        $arFilters = [];
        if ($filter) {
            foreach ($filter as $item) {
                $arFilters[] = json_decode(htmlspecialchars_decode($item), true);
            }
        }

        $this->data['response']['total'] = 0;
        $this->data['response']['items'] = [];

        if ($arFilters || $date_from || $date_to || $user_name || $events) {


            $audit = AuditEvent::leftJoin(
                'audit_users',
                'audit_users.id',
                '=',
                'audit_events.audit_user_id'
            )->leftJoin(
                'audit_users as audit_aliases',
                'audit_aliases.id',
                '=',
                'audit_events.audit_alias_id'
            )->leftJoin(
                'audit_models',
                'audit_models.id',
                '=',
                'audit_events.main_auditable_model_id'
            );

            if (is_array($arFilters) && !empty($arFilters)) {
                $auditableTypes = [];
                $auditableIds = [];
                $attributeNames = [];
                foreach ($arFilters as $arFilter) {
                    $auditableTypes[] = $arFilter['auditable_type'];
                    if ($arFilter['auditable_id']) {
                        $auditableIds[] = $arFilter['auditable_id'];
                    }
                    if (is_array($arFilter['field_name'])) {
                        $attributeNames = array_merge($attributeNames, $arFilter['field_name']);
                    }
                }

                if (!empty($auditableTypes)) {
                    $models = AuditModel::select(['id'])->whereIn('name', $auditableTypes)->get();
                    $auditableTypes = [];
                    if ($models) {
                        foreach ($models as $model) {
                            $auditableTypes[] = $model->getKey();
                        }
                        $audit = $audit->whereIn('main_auditable_model_id', $auditableTypes);
                    }
                }

                if (!empty($auditableIds)) {
                    $audit = $audit->whereIn('main_auditable_id', $auditableIds);
                }

                if (!empty($attributeNames)) {
                    $audit = $audit->join(
                        'audit_event_descriptions',
                        'audit_event_descriptions.audit_event_id',
                        '=',
                        'audit_events.id'
                    )->groupBy('audit_events.id')
                     ->whereIn(
                         'audit_event_descriptions.field_name',
                         $attributeNames
                     );
                }

            }
            if ($date_from) {
                $audit = $audit->where('date_added', '>=', $date_from);
            }
            if ($date_to) {
                $audit = $audit->where('date_added', '<=', $date_to.' 23.59.59');
            }
            if ($user_name) {
                $audit = $audit->where(function ($query) use ($user_name) {
                    /** @var QueryBuilder $query */
                    $query->where('audit_users.name', 'like', '%'.$user_name.'%')
                        ->orWhere('audit_aliases.name', 'like', '%'.$user_name.'%');
                });
            }

            if ($events && is_array($events)) {
                foreach ($events as &$event) {
                    $event = AuditEvent::EVENT_NAMES[strtolower($event)];
                }
                $audit = $audit->whereIn('event_type_id', $events);
            }

            $audit = $audit->select([
                $this->db->raw('SQL_CALC_FOUND_ROWS request_id, event_type_id, date_added, '.$this->db->prefix().
                    'audit_models.name as main_auditable_model, main_auditable_id, '
                    .$this->db->prefix().'audit_users.name as user_name, '.$this->db->prefix().'audit_events.id, '
                    .$this->db->prefix().'audit_aliases.name as alias_name'),
            ]);

            if ($rowsPerPage > 0) {
                $audit = $audit
                    ->offset($page * $rowsPerPage - $rowsPerPage)
                    ->limit($rowsPerPage);
            }

            if ($sortBy) {
                $ordering = 'ASC';
                if ($descending == 'true' or $descending === true) {
                    $ordering = 'DESC';
                }
                if ($sortBy == 'event') {
                    $sortBy = 'event_type_id';
                }
                $audit = $audit->orderBy($sortBy, $ordering);
            }

            //$this->db->enableQueryLog();

            $this->data['response']['items'] = $audit
                ->get()
                ->toArray();
            foreach ($this->data['response']['items'] as &$item) {
                $item['event'] = AuditEvent::getEventById($item['event_type_id']);
            }

            //\H::df($this->db->getQueryLog());

            $this->data['response']['total'] = $this->db->sql_get_row_count();

        }
        return $this->data['response'];
    }

    /**
     * * Method for get Audit log event description from storage (DB, ElasticSearch, etc)
     *
     * @param array $request
     *
     * @return mixed
     */
    public function getEventDetail(array $request)
    {
        /**
         * @var string $filter
         */
        extract($request, EXTR_OVERWRITE);
        $arFilters = [];
        if ($filter) {
            $arFilters = json_decode(htmlspecialchars_decode($filter), true);
        }

        $this->data['response']['items'] = [];

        //  $this->db->enableQueryLog();

        if ($arFilters) {
            $audit = new AuditEventDescription();
            $audit = $audit->leftJoin('audit_models', 'audit_models.id', '=', 'audit_event_descriptions.auditable_model_id');
            $audit = $audit->select(['audit_models.name as auditable_model', 'field_name', 'old_value', 'new_value']);
            foreach ($arFilters as $key => $value) {
                $audit = $audit->where($key, $value);
            }
            $audit = $audit->groupBy('audit_models.name')->groupBy('field_name');
            $audit = $audit->orderBy('audit_models.name')->orderBy('field_name');
            $this->data['response']['items'] = $audit
                ->get()
                ->toArray();
        }


        foreach ($this->data['response']['items'] as &$item) {
            if ($item['old_value'] === null) {
                $item['old_value'] = 'Empty';
            } else {
                $item['old_value'] = htmlspecialchars_decode($item['old_value']);
            }
            if ($item['new_value'] === null) {
                $item['new_value'] = 'Empty';
            } else {
                $item['new_value'] = htmlspecialchars_decode($item['new_value']);
            }
        }

        $this->data['response']['total'] = count($this->data['response']['items']);

        return $this->data['response'];
    }
}