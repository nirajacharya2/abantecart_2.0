<?php

namespace abc\controllers\admin;

use abc\core\engine\AController;
use abc\core\lib\AJson;
use abc\models\system\Audit;
use abc\models\system\AuditEvent;
use abc\models\system\AuditEventDescription;
use abc\models\system\AuditModel;

class ControllerResponsesToolAuditAjax extends AController
{

    public $data;

    public function main()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        if (isset($this->request->get['getDetail'])) {
            $this->getDetail();
            return;
        }

        /**
         * @var string $filter
         * @var string $date_from
         * @var string $date_to
         * @var string $user_name
         * @var string $page
         * @var string $rowsPerPage
         * @var string $sortBy
         * @var string $descending
         * @var array $events
         */
        extract($this->request->get);

        if ($filter) {
            $arFilters = [];
            foreach ($filter as $item) {
                $arFilters[] = json_decode(htmlspecialchars_decode($item), true);
            }
        }

        $this->data['response']['total'] = 0;
        $this->data['response']['items'] = [];


        if ( $arFilters || $date_from || $date_to || $user_name || $events) {


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
                    $audit = $audit->join('audit_event_descriptions', 'audit_event_descriptions.audit_event_id','=','audit_events.id')
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

            $audit = $audit->select([$this->db->raw('SQL_CALC_FOUND_ROWS request_id, event_type_id, date_added, '.$this->db->prefix().'audit_models.name as main_auditable_model, main_auditable_id, '
                .$this->db->prefix().'audit_users.name as user_name, '.$this->db->prefix().'audit_events.id, '.$this->db->prefix().'audit_aliases.name as alias_name')]);

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

             // $this->db->enableQueryLog();

            $this->data['response']['items'] = $audit
                ->get()
                ->toArray();
            foreach ($this->data['response']['items'] as &$item) {
                $item['event'] = AuditEvent::getEventById($item['event_type_id']);
            }

            //\H::df($this->db->getQueryLog());

            $this->data['response']['total'] = $this->db->sql_get_row_count();

        }

        $this->load->library('json');
        $this->response->setOutput(AJson::encode($this->data['response']));

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function getDetail()
    {
        /**
         * @var string $filter
         */
        extract($this->request->get);

        if ($filter) {
            $arFilters = json_decode(htmlspecialchars_decode($filter), true);
        }

        $this->data['response']['items'] = [];

      //  $this->db->enableQueryLog();

        if ($arFilters) {
            $audit = new AuditEventDescription();
            $audit = $audit->leftJoin('audit_models', 'audit_models.id', '=', 'audit_event_descriptions.auditable_model_id' );
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

        //\H::df($this->db->getQueryLog());

        foreach ($this->data['response']['items'] as &$item) {
            if ($item['old_value'] === null) {
                $item['old_value'] = 'Empty';
            }
            if ($item['new_value'] === null) {
                $item['new_value'] = 'Empty';
            }
        }

        $this->data['response']['total'] = count($this->data['response']['items']);

        $this->load->library('json');
        $this->response->setOutput(AJson::encode($this->data['response']));
    }

}
