<?php

namespace abc\controllers\admin;

use abc\core\engine\AController;
use abc\core\lib\AJson;
use abc\models\system\Audit;

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


            $audit = Audit::whereRaw("1 = 1")->groupBy('request_id')->groupBy('main_auditable_model');
            if (is_array($arFilters) && !empty($arFilters)) {
                $auditableTypes = [];
                $auditableIds = [];
                $attributeNames = [];
                foreach ($arFilters as $arFilter) {
                    $auditableTypes[] = $arFilter['auditable_type'];
                    if ($arFilter['auditable_id']) {
                        $auditableIds[] = $arFilter['auditable_id'];
                    }
                    $attributeNames = array_merge($attributeNames, $arFilter['attribute_name']);
                }

                if (!empty($auditableTypes)) {
                    $audit = $audit->whereIn('main_auditable_model', $auditableTypes);
                }

                if (!empty($auditableIds)) {
                    $audit = $audit->whereIn('main_auditable_id', $auditableIds);
                }

                if (!empty($attributeNames)) {
                    $audit = $audit->whereIn('attribute_name', $attributeNames);
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
                    $query->where('user_name', 'like', '%'.$user_name.'%')
                        ->orWhere('alias_name', 'like', '%'.$user_name.'%');
                });
            }

            if ($events && is_array($events)) {
                foreach ($events as &$event) {
                    $event = strtolower($event);
                }
                $audit = $audit->whereIn('event', $events);
            }

            $audit = $audit->select([$this->db->raw('*')]);

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
                $audit = $audit->orderBy($sortBy, $ordering);
            }

              //$this->db->enableQueryLog();

            $this->data['response']['items'] = $audit
                ->get()
                ->toArray();

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

        if ($arFilters) {
            $audit = new Audit();
            foreach ($arFilters as $key => $value) {
                $audit = $audit->where($key, $value);
            }
            $this->data['response']['items'] = $audit
                ->get()
                ->toArray();
        }

        foreach ($this->data['response']['items'] as &$item) {
            if (!$item['old_value'] && $item['old_value'] !== "0" && $item['old_value'] !== 0) {
                $item['old_value'] = 'Empty';
            }
            if (empty($item['new_value']) && $item['new_value'] !== "0" && $item['new_value'] !== 0) {
                $item['new_value'] = 'Empty';
            }
        }

        $this->data['response']['total'] = count($this->data['response']['items']);

        $this->load->library('json');
        $this->response->setOutput(AJson::encode($this->data['response']));
    }

}
