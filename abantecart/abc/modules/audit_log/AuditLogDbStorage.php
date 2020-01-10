<?php

namespace abc\modules\audit_log;

use abc\core\engine\Registry;
use abc\core\lib\contracts\AuditLogStorageInterface;
use abc\models\system\AuditEvent;
use abc\models\system\AuditEventDescription;
use abc\models\system\AuditModel;
use abc\models\system\AuditUser;

class AuditLogDbStorage implements AuditLogStorageInterface
{
    private $db;
    private $data;

    /**
     * AuditLogDbStorage constructor.
     */
    public function __construct()
    {
        $this->db = Registry::getInstance()->get('db');
    }

    /**
     * Method for write Audit log data to storage (DB, ElasticSearch, etc)
     *
     * @param array $data
     *
     * @return mixed
     */
    public function write(array $data)
    {
        $auditModel = $this->db->table('audit_models')
            ->where('name', '=', $data['entity']['name'])
            ->first();
        if ($auditModel) {
            $auditableModelId = $auditModel->id;
        } else {
            $auditableModelId = $this->db->table('audit_models')->insertGetId(['name' => $data['entity']['name']]);
        }
        $data['entity']['model_id'] = $auditableModelId;

        $db = $this->db;
        $this->db->transaction(static function () use ($db, $data) {

            /*$auditSession = $db->table('audit_sessions')
                ->where('session_id', '=', $data['event']['session_id'])
                ->first();
            if ($auditSession) {
                $auditSessionId = $auditSession->id;
            } else {
                $auditSessionId = $db->table('audit_sessions')->insertGetId(['session_id' => $data['event']['session_id']]);
            }
            $event['audit_session_id'] = $auditSessionId;*/

            $auditUser = $db->table('audit_users')
                ->where('user_type_id', '=', AuditUser::getUserTypeId($data['actor']['group']))
                ->where('user_id', '=', $data['actor']['id'])
                ->where('name', '=', $data['actor']['name'])
                ->first();
            if ($auditUser) {
                $auditUserId = $auditUser->id;
            } else {
                $userData = [
                    'user_type_id' => AuditUser::getUserTypeId($data['actor']['group']),
                    'user_id'      => $data['actor']['id'],
                    'name'         => $data['actor']['name'],
                ];
                $auditUserId = $db->table('audit_users')->insertGetId($userData);
            }
            $event['audit_user_id'] = $auditUserId;
            $event['request_id'] = $data['id'];
            $event['event_type_id'] = AuditEvent::EVENT_NAMES[$data['entity']['group']] ?: 1;
            $event['main_auditable_model_id'] = $data['entity']['model_id'];
            $event['main_auditable_id'] = $data['entity']['id'];

            $auditEvent = $db->table('audit_events')
                ->where('request_id', '=', $event['request_id'])
                ->where('audit_user_id', '=', $auditUserId)
                ->where('event_type_id', '=', $event['event_type_id'])
                ->where('main_auditable_model_id', '=', $event['main_auditable_model_id'])
                ->where('main_auditable_id', '=', $event['main_auditable_id'])
                ->first();
            if ($auditEvent) {
                $eventId = $auditEvent->id;
            } else {
                $eventId = $db->table('audit_events')->insertGetId($event);
            }

            if ($eventId) {
                foreach ($data['changes'] as $change) {
                    if (!$change['groupName']) {
                        $change['groupName'] = $change['name'];
                    }
                    $model = $db->table('audit_models')
                        ->where('name', '=', $change['groupName'])
                        ->first();
                    if ($model) {
                        $modelId = $model->id;
                    } else {
                        $modelId = $db->table('audit_models')->insertGetId(['name' => $change['groupName']]);
                    }
                    $change['model_id'] = $modelId;
                    $eventDescription = [
                        'auditable_model_id' => $change['model_id'],
                        'auditable_id'       => $change['groupId'] ?: 0,
                        'field_name'         => $change['name'],
                        'old_value'          => $change['oldValue'],
                        'new_value'          => $change['newValue'],
                        'audit_event_id'     => $eventId,
                    ];
                    $db->table('audit_event_descriptions')->insert($eventDescription);
                }
            }
        });
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
         * @var string $filter
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

        if ($filter) {
            $arFilters = [];
            foreach ($filter as $item) {
                $arFilters[] = json_decode(htmlspecialchars_decode($item), true);
            }
        }

        $this->data['response']['total'] = 0;
        $this->data['response']['items'] = [];

        if ($arFilters || $date_from || $date_to || $user_name || $events) {


            $audit = AuditEvent::leftJoin('audit_users', 'audit_users.id', '=', 'audit_events.audit_user_id')
                ->leftJoin('audit_users as audit_aliases', 'audit_aliases.id', '=', 'audit_events.audit_alias_id')
                ->leftJoin('audit_models', 'audit_models.id', '=', 'audit_events.main_auditable_model_id');
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
                    $audit = $audit->join('audit_event_descriptions', 'audit_event_descriptions.audit_event_id', '=', 'audit_events.id')
                        ->groupBy('audit_events.id')
                        ->whereIn('audit_event_descriptions.field_name', $attributeNames);
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

            $this->data['response']['items'] = $audit
                ->get()
                ->toArray();
            foreach ($this->data['response']['items'] as &$item) {
                $item['event'] = AuditEvent::getEventById($item['event_type_id']);
            }

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