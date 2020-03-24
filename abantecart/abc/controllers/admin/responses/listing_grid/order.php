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

use abc\core\ABC;
use abc\core\engine\AController;
use abc\core\lib\AError;
use abc\core\lib\AException;
use abc\core\lib\AJson;
use abc\models\order\Order;
use abc\models\order\OrderStatus;
use Exception;
use H;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;
use stdClass;

class ControllerResponsesListingGridOrder extends AController
{
    public $error = [];
    public $data = [];

    public function main()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('sale/order');

        $page = $this->request->post['page']; // get the requested page
        $limit = $this->request->post['rows']; // get how many rows we want to have into the grid
        $sidx = $this->request->post['sidx']; // get index row - i.e. user click to sort
        $sord = $this->request->post['sord']; // get the direction

        // process jGrid search parameter
        $allowedFields = array_merge(['name', 'order_id', 'date_added', 'total', 'date_end', 'date_start'],
            (array)$this->data['allowed_fields']);
        $allowedSortFields = array_merge(['customer_id', 'order_id', 'name', 'status', 'date_added', 'total'],
            (array)$this->data['allowed_sort_fields']);

        $allowedDirection = ['asc', 'desc'];

        if (!in_array($sidx, $allowedSortFields)) {
            $sidx = $allowedSortFields[0];
        }
        if (!in_array($sord, $allowedDirection)) {
            $sord = $allowedDirection[0];
        }

        $data = [
            'sort'  => $sidx,
            'order' => $sord,
            'start' => ($page - 1) * $limit,
            'limit' => $limit,
        ];
        if (isset($this->request->get['status']) && $this->request->get['status'] !== 'default') {
            $data['filter']['order_status_id'] = $this->request->get['status'];
        }
        if (isset($this->request->get['date_end']) && $this->request->get['date_end'] !== '') {
            $data['filter']['date_end'] = H::dateDisplay2ISO($this->request->get['date_end']);
        }
        if (isset($this->request->get['date_start']) && $this->request->get['date_start'] !== '') {
            $data['filter']['date_start'] = H::dateDisplay2ISO($this->request->get['date_start']);
        }
        if (H::has_value($this->request->get['customer_id'])) {
            $data['filter']['customer_id'] = $this->request->get['customer_id'];
        }
        if (H::has_value($this->request->get['product_id'])) {
            $data['filter']['product_id'] = $this->request->get['product_id'];
        }

        if (isset($this->request->post['_search']) && $this->request->post['_search'] == 'true') {
            $searchData = json_decode(htmlspecialchars_decode($this->request->post['filters']), true);

            foreach ($searchData['rules'] as $rule) {
                if (!in_array($rule['field'], $allowedFields)) {
                    continue;
                }
                $data['filter'][$rule['field']] = $rule['data'];
                if ($rule['field'] == 'date_added') {
                    $data['filter'][$rule['field']] = H::dateDisplay2ISO($rule['data']);
                }
            }
        }

        $results = OrderStatus::with('description')
                              ->where('display_status', '=', '1')
                              ->get();
        $statuses = [];
        foreach ($results->toArray() as $item) {
            $statuses[(string)$item['order_status_id']] = $item['description']['name'];
        }

        $results = Order::search($data);
        $total = $results[0]['total_num_rows'];
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

        $i = 0;
        foreach ($results->toArray() as $result) {
            $response->rows[$i]['id'] = $result['order_id'];
            $response->userdata->order_status_id[$result['order_id']] = $result['order_status_id'];
            //if status not-reversal or not displayed
            if (in_array($this->order_status->getStatusById($result['order_status_id']),
                    (array)ABC::env('ORDER')['not_reversal_statuses'])
                || !in_array($result['order_status_id'], array_keys($statuses))) {
                $orderStatus = $result['status'];
            } else {
                $orderStatus = $this->html->buildSelectBox(
                    [
                        'name'    => 'order_status_id['.$result['order_id'].']',
                        'value'   => $result['order_status_id'],
                        'options' => $statuses,
                    ]
                );
            }

            $response->rows[$i]['cell'] = [
                $result['order_id'],
                $result['name'],
                $orderStatus,
                H::dateISO2Display(
                    $result['date_added'],
                    $this->language->get('date_format_short')." ".$this->language->get('time_format_short')
                ),
                $this->currency->format($result['total'], $result['currency'], $result['value']),
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

        $this->loadLanguage('sale/order');
        if (!$this->user->canModify('listing_grid/order')) {
            $error = new AError('');

            return $error->toJSONResponse('NO_PERMISSIONS_402',
                [
                    'error_text'  => sprintf($this->language->get('error_permission_modify'), 'listing_grid/order'),
                    'reset_value' => true,
                ]);
        }

        switch ($this->request->post['oper']) {
            case 'del':
                $ids = explode(',', $this->request->post['id']);
                $this->db->beginTransaction();
                try {
                    if (!empty($ids)) {
                        Order::whereIn('order_id', $ids)->forceDelete();
                    }
                    $this->db->commit();
                } catch (Exception $e) {
                    $this->db->rollback();
                    $error = new AError('');

                    return $error->toJSONResponse('APP_ERROR_402',
                        [
                            'error_text'  => 'Application Error occurred. Probably some of orders cannot be removed by cause data dependency. Please see error log for details.',
                            'reset_value' => true,
                        ]
                    );
                }
                break;
            case 'save':
                $ids = explode(',', $this->request->post['id']);
                if (!empty($ids)) {
                    $this->db->beginTransaction();
                    try {
                        foreach ($ids as $id) {
                            Order::editOrder(
                                $id,
                                ['order_status_id' => (int)$this->request->post['order_status_id'][$id]]
                            );
                        }
                    } catch (Exception $e) {
                        $this->db->rollback();
                        $error = new AError('');
                        return $error->toJSONResponse('APP_ERROR_402',
                            [
                                'error_text'  => 'Application Error occurred. Please see error log for details.',
                                'reset_value' => true,
                            ]
                        );
                    }
                }
                break;
            default:
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        return null;
    }

    /**
     * update only one field
     *
     * @return void
     * @throws AException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public function update_field()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('sale/order');

        if (!$this->user->canModify('listing_grid/order')) {
            $error = new AError('');

            return $error->toJSONResponse('NO_PERMISSIONS_402',
                [
                    'error_text'  => sprintf($this->language->get('error_permission_modify'), 'listing_grid/order'),
                    'reset_value' => true,
                ]);
        }

        if (H::has_value($this->request->post['downloads'])) {
            $data = $this->request->post['downloads'];
            $this->loadModel('catalog/download');
            foreach ($data as $order_download_id => $item) {
                if (isset($item['expire_date'])) {
                    $item['expire_date'] = $item['expire_date'] ? H::dateDisplay2ISO($item['expire_date'],
                        $this->language->get('date_format_short')) : '';
                }
                $this->model_catalog_download->editOrderDownload($order_download_id, $item);
            }

            return null;
        }

        if (isset($this->request->get['id'])) {
            try {
                Order::editOrder($this->request->get['id'], $this->request->post);
                $this->session->data['success'] = $this->language->get('text_success');
            } catch (AException $e) {
                $error = new AError('');
                return $error->toJSONResponse('APP_ERROR_402',
                    [
                        'error_text'  => $e->getMessage(),
                        'reset_value' => true,
                    ]);
            }
            return null;
        }

        //request sent from jGrid. ID is key of array
        $update = [];
        foreach ($this->request->post as $field => $value) {
            foreach ($value as $orderId => $v) {
                $update[$orderId][$field] = $v;
            }
        }
        //now update orders
        foreach ($update as $orderId => $values) {
            try {
                Order::editOrder($orderId, $values);
            } catch (AException $e) {
                $error = new AError('');
                return $error->toJSONResponse('APP_ERROR_402',
                    [
                        'error_text'  => $e->getMessage(),
                        'reset_value' => true,
                    ]);
            }
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function summary()
    {

        //update controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('sale/order');

        $response = new stdClass();

        if (isset($this->request->get['order_id'])) {
            $order_id = $this->request->get['order_id'];
        } else {
            $order_id = 0;
        }

        $order_info = Order::getOrderArray($order_id, 'any');

        if (empty($order_info)) {
            $response->error = $this->language->get('error_order_load');
        } else {
            $response->order = [
                'order_id'        => '#'.$order_info['order_id'],
                'name'            => $order_info['firstname'].''.$order_info['lastname'],
                'email'           => $order_info['email'],
                'telephone'       => $order_info['telephone'],
                'date_added'      => H::dateISO2Display($order_info['date_added'],
                    $this->language->get('date_format_short')),
                'total'           => $this->currency->format($order_info['total'], $order_info['currency'],
                    $order_info['value']),
                'order_status'    => $order_info['order_status_id'],
                'shipping_method' => $order_info['shipping_method'],
                'payment_method'  => $order_info['payment_method'],
            ];

            if ($order_info['customer_id']) {
                $response->order['name'] = '<a href="'.$this->html->getSecureURL('sale/customer/update',
                        '&customer_id='.$order_info['customer_id']).'">'.$response->order['name'].'</a>';
            }

            $status = OrderStatus::with('description')->find($order_info['order_status_id']);
            if ($status) {
                $response->order['order_status'] = $status['description']['name'];
            }
        }
        $this->data['response'] = $response;
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->load->library('json');
        $this->response->setOutput(AJson::encode($this->data['response']));
    }

}