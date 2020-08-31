<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2017 Belavier Commerce LLC

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

use abc\core\engine\AController;
use abc\core\engine\Registry;
use abc\core\lib\AError;
use abc\core\lib\AJson;
use abc\models\order\Order;
use abc\models\order\OrderStatus;
use abc\models\order\OrderStatusDescription;
use H;
use Illuminate\Validation\ValidationException;
use stdClass;

class ControllerResponsesListingGridOrderStatus extends AController
{
    public $data = [];

    public function main()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('localisation/order_status');

        $page = $this->request->post['page']; // get the requested page
        $limit = $this->request->post['rows']; // get how many rows we want to have into the grid
        $sidx = $this->request->post['sidx']; // get the direction
        $sord = $this->request->post['sord']; // get the direction

        // process jGrid search parameter
        $allowedDirection = ['asc', 'desc'];

        if (!in_array($sord, $allowedDirection)) {
            $sord = $allowedDirection[0];
        }

        $data = [
            'sort'                => $sidx,
            'order'               => strtoupper($sord),
            'start'               => ($page - 1) * $limit,
            'limit'               => $limit,
            'content_language_id' => $this->session->data['content_language_id'],
        ];

        $total = OrderStatus::count();
        if ($total > 0) {
            $total_pages = ceil($total / $limit);
        } else {
            $total_pages = 0;
        }

        if ($page > $total_pages) {
            $page = $total_pages;
            $data['start'] = ($page - 1) * $limit;
        }

        $response = new stdClass();
        $response->page = $page;
        $response->total = $total_pages;
        $response->records = $total;

        $results = OrderStatus::getOrderStatuses($data);
        $i = 0;

        $base_order_statuses = $this->order_status->getBaseStatuses();

        foreach ($results as $result) {
            $id = $result['order_status_id'];
            $response->rows[$i]['id'] = $id;
            if (H::has_value($base_order_statuses[$id])) {
                $response->userdata->classes[$id] = 'disable-delete';
            }
            $response->rows[$i]['cell'] = [
                $this->html->buildInput([
                    'name'  => 'order_status['.$id.'][name]',
                    'value' => $result['name'],
                ]),
                $result['status_text_id'],
                mb_strtoupper($result['display_status'] ? $this->language->get('text_on') : $this->language->get('text_off')),
            ];
            $i++;
        }
        $this->data['response'] = $response;
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
        $this->load->library('json');
        $this->response->setOutput(AJson::encode($this->data['response']));
    }

    public function update()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        if (!$this->user->canModify('listing_grid/order_status')) {
            $error = new AError('');
            return $error->toJSONResponse('NO_PERMISSIONS_402',
                [
                    'error_text'  => sprintf($this->language->get('error_permission_modify'),
                        'listing_grid/order_status'),
                    'reset_value' => true,
                ]);
        }
        $this->loadModel('setting/store');
        $this->loadLanguage('localisation/order_status');
        $this->db->beginTransaction();
        switch ($this->request->post['oper']) {
            case 'del':
                $ids = explode(',', $this->request->post['id']);
                if (!empty($ids)) {
                    try {
                        foreach ($ids as $id) {
                            $err = $this->_validateDelete($id);
                            if (!empty($err)) {
                                $error = new AError('');
                                return $error->toJSONResponse('VALIDATION_ERROR_406', ['error_text' => $err]);
                            }
                            $oStatus = OrderStatus::find($id);
                            if ($oStatus) {
                                $oStatus->forceDelete();
                            }
                        }
                        $this->db->commit();
                    } catch (\Exception $e) {
                        Registry::log()->write(__CLASS__.': '.$e->getMessage());
                        $this->db->rollback();
                        $error = new AError('');
                        return $error->toJSONResponse(
                            'VALIDATION_ERROR_406',
                            ['error_text' => 'Application Error! See error log for details.']
                        );
                    }
                }
                break;
            case 'save':
                $ids = explode(',', $this->request->post['id']);
                if (!empty($ids)) {

                    try {
                        foreach (array_unique($ids) as $id) {
                            if (isset($this->request->post['order_status'][$id])) {
                                foreach ($this->request->post['order_status'][$id] as $key => $value) {
                                    if (!$this->validateStatusName($value)) {
                                        $this->response->setOutput($this->language->get('error_name'));
                                        return null;
                                    }

                                    OrderStatusDescription::updateOrInsert(
                                        [
                                            'order_status_id' => $id,
                                            'language_id'     => $this->language->getContentLanguageID(),
                                        ],
                                        ['name' => $value]);
                                }

                            }
                        }
                        $this->db->commit();
                    } catch (\Exception $e) {
                        Registry::log()->write(__CLASS__.': '.$e->getMessage());
                        $this->db->rollback();
                        $error = new AError('');
                        return $error->toJSONResponse('VALIDATION_ERROR_406',
                            ['error_text' => 'Application Error! See error log for details.']);
                    }
                }
                break;
            default:
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    /**
     * update only one field
     *
     * @return void
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     */
    public function update_field()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        if (!$this->user->canModify('listing_grid/order_status')) {
            $error = new AError('');
            return $error->toJSONResponse('NO_PERMISSIONS_402',
                [
                    'error_text'  => sprintf($this->language->get('error_permission_modify'),
                        'listing_grid/order_status'),
                    'reset_value' => true,
                ]);
        }

        $post = $this->request->post;
        $this->loadLanguage('localisation/order_status');
        if (isset($this->request->get['id']) && !empty($post)) {
            //request sent from edit form. ID in url
            $fields = ['name', 'status_text_id'];

            foreach ($fields as $field_name) {
                if (isset($post[$this->request->get['id']][$field_name])) {
                    if (!$this->validateStatusName($post[$this->request->get['id']][$field_name])) {
                        $error = new AError('');
                        return $error->toJSONResponse('VALIDATION_ERROR_406',
                            ['error_text' => $this->language->get('error_'.$field_name)]);
                    }
                }
            }
            $orderStatusId = $this->request->get['id'];
            $this->db->beginTransaction();
            try {
                $oStatus = OrderStatus::find($orderStatusId);
                $oStatus->update($post);
                $orderStatusDesc = OrderStatusDescription::where(
                    [
                        'order_status_id' => $orderStatusId,
                        'language_id'     => $this->language->getContentLanguageID(),
                    ]
                )->first();
                $orderStatusDesc->update($post);
                $this->db->commit();
            } catch (\Exception $e) {
                Registry::log()->write(__CLASS__.': '.$e->getMessage());
                $this->db->rollback();
                $error = new AError('');
                return $error->toJSONResponse('VALIDATION_ERROR_406',
                    ['error_text' => 'Application Error! See error log for details.']);
            }

            return null;
        }

        //request sent from jGrid. ID is key of array
        if (isset($this->request->post['order_status'])) {
            foreach ($this->request->post['order_status'] as $id => $value) {

                if (!$this->validateStatusName($value['name'])) {
                    $error = new AError('');
                    return $error->toJSONResponse('VALIDATION_ERROR_406',
                        ['error_text' => $this->language->get('error_name')]);
                }
                OrderStatusDescription::updateOrInsert(
                    [
                        'order_status_id' => $id,
                        'language_id'     => $this->language->getContentLanguageID(),
                    ],
                    ['name' => $value['name']]
                );
            }
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    protected function validateStatusName($name)
    {
        $this->error = [];
        $oStatusDesc = new OrderStatusDescription();
        try {
            $oStatusDesc->validate(['name' => $name]);
        } catch (ValidationException $e) {
            H::SimplifyValidationErrors($oStatusDesc->errors()['validation'], $this->error);
        }

        return !($this->error);
    }

    private function _validateDelete($order_status_id)
    {

        if (in_array($order_status_id, array_keys($this->order_status->getBaseStatuses()))) {
            return $this->language->get('error_nondeletable');
        }

        if ($this->config->get('config_order_status_id') == $order_status_id) {
            return $this->language->get('error_default');
        }

        $store_total = $this->model_setting_store->getTotalStoresByOrderStatusId($order_status_id);
        if ($store_total) {
            return sprintf($this->language->get('error_store'), $store_total);
        }

        $order_id = Order::where('order_status_id', '=', $order_status_id)->first()->order_id;
        if ($order_id) {
            return sprintf($this->language->get('error_order'), $order_id);
        }
    }

}
