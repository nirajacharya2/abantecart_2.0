<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2023 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/

namespace abc\controllers\admin;

use abc\core\ABC;
use abc\core\engine\AController;
use abc\core\lib\AJson;
use abc\core\lib\APromotion;
use abc\extensions\incentive\models\IncentiveApplied;
use stdClass;

class ControllerResponsesListingGridIncentiveApplied extends AController
{
    public function main()
    {
        $this->loadLanguage('incentive/incentive');
        $page = (int)$this->request->post['page'] ?: 1;
        $limit = $this->request->post['rows'];
        $sort = $this->request->post['sidx'];
        $order = $this->request->post['sord'];
        $filter_data = [
            'incentive_id' => $this->request->get['incentive_id'],
            'start_date'   => $this->request->get['start_date'],
            'end_date'     => $this->request->get['end_date']
        ];
        if (isset($this->request->post['_search']) && $this->request->post['_search'] == 'true') {
            $searchData = AJson::decode(htmlspecialchars_decode($this->request->post['filters']), true);
            $allowedFields = array_merge(
                ['customer', 'customer_id', 'incentive_id', 'start_date', 'end_date', 'bonus_amount'],
                (array)$this->data['allowed_fields']
            );
            foreach ($searchData['rules'] as $rule) {
                if (!in_array($rule['field'], $allowedFields)) {
                    continue;
                }
                $filter_data[$rule['field']] = $rule['data'];
            }
        }

        $this->data['search_parameters'] = [
            'filter'      => [
                'customer'     => $filter_data['customer'],
                'customer_id'  => $filter_data['customer_id'],
                'incentive_id' => $filter_data['incentive_id'],
                'start_date'   => $filter_data['start_date'],
                'end_date'     => $filter_data['end_date'],
                'bonus_amount' => $filter_data['bonus_amount'],
            ],
            'language_id' => $this->language->getContentLanguageID(),
            'start'       => ($page - 1) * $limit,
            'limit'       => $limit,
            'sort'        => $sort,
            'order'       => $order
        ];
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $results = IncentiveApplied::getItems($this->data['search_parameters']);

        $total = $results->total;
        $total_pages = $total > 0 ? ceil($total / $limit) : 0;

        $response = new stdClass();
        $response->page = $page;
        $response->total = $total_pages;
        $response->records = $total;
        $response->userdata = (object)[''];

        $results = $results ?: [];

        foreach ($results as $result) {
            $response->rows[] = [
                'id'   => $result['id'],
                'cell' => [
                    $result['customer_name'],
                    $result['name'],
                    $this->currency->format($result['bonus_amount']),
                    $result['date_added']->toDatetimeString(),
                    (string)$this->html->buildElement(
                        [
                            'type'  => 'button',
                            'text'  => (!$result['result_code'] ? ' Success' : ' Fail'),
                            'style' => 'btn btn-xs ' . (!$result['result_code'] ? 'btn-link' : 'btn-warning'),
                            'icon'  => 'fa ' . (!$result['result_code'] ? 'fa-thumbs-up' : 'fa-exclamation-triangle'),
                            'href'  => 'Javascript:void(0);',
                            'attr'  => 'disabled'
                        ]
                    )
                ]
            ];
        }
        $this->data['output'] = $response;
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->response->setOutput(AJson::encode($this->data['output']));
    }

    public function details()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $id = $this->request->get['id'];
        $details = IncentiveApplied::with('incentive', 'incentive.description', 'customer')->find($id);
        /** @var APromotion $promo */
        $promo = ABC::getObjectByAlias('APromotion');
        $this->data['conditionList'] = $promo->getConditionList(
            $details['incentive']['conditions']['condition_type']
        );
        $this->data['details'] = $details->toArray();
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
        $this->view->batchAssign($this->data);
        $this->processTemplate('responses/incentive/applied_details.tpl');
    }
}