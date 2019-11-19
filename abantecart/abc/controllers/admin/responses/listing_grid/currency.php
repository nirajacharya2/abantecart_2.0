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
use abc\core\lib\AError;
use abc\core\lib\AJson;
use abc\models\locale\Currency;
use abc\models\order\Order;
use H;
use stdClass;


class ControllerResponsesListingGridCurrency extends AController
{
    public $data = [];

    public function main()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('localisation/currency');

        $page = $this->request->post['page']; // get the requested page
        $limit = $this->request->post['rows']; // get how many rows we want to have into the grid
        $sidx = $this->request->post['sidx']; // get index row - i.e. user click to sort
        $sord = $this->request->post['sord']; // get the direction

        $data = [
            'sort'  => $sidx,
            'order' => strtoupper($sord),
            'start' => ($page - 1) * $limit,
            'limit' => $limit,
        ];

        $total = Currency::count();
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

        $currencyInstance = new Currency();

        $results = $currencyInstance->setGridRequest($data)->get()->toArray();

        $i = 0;
        foreach ($results as $result) {

            $response->rows[$i]['id'] = $result['currency_id'];
            $response->rows[$i]['cell'] = [
                $this->html->buildInput([
                    'name'  => 'title['.$result['currency_id'].']',
                    'value' => $result['title'],
                ]),
                $this->html->buildInput([
                    'name'  => 'code['.$result['currency_id'].']',
                    'value' => $result['code'],
                ]),
                $this->html->buildInput([
                    'name'  => 'value['.$result['currency_id'].']',
                    'value' => $result['value'],
                ]),
                H::dateISO2Display($result['date_modified'], $this->language->get('date_format_short')),
                $this->html->buildCheckbox([
                    'name'  => 'status['.$result['currency_id'].']',
                    'value' => $result['status'],
                    'style' => 'btn_switch',
                ]),
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

        $this->loadLanguage('localisation/currency');
        if (!$this->user->canModify('listing_grid/currency')) {
            $error = new AError('');
            return $error->toJSONResponse('NO_PERMISSIONS_402',
                [
                    'error_text'  => sprintf($this->language->get('error_permission_modify'), 'listing_grid/currency'),
                    'reset_value' => true,
                ]);
        }

        switch ($this->request->post['oper']) {
            case 'del':

                $this->loadModel('setting/store');

                $ids = explode(',', $this->request->post['id']);
                if (!empty($ids)) {
                    $ids = array_unique($ids);
                    foreach ($ids as $id) {
                        $err = '';
                        $currency_info = Currency::find($id)->toArray();
                        if ($currency_info) {
                            if ($this->config->get('config_currency') == $currency_info['code']) {
                                $err = $this->language->get('error_default');
                            }

                            $store_total = $this->model_setting_store->getTotalStoresByCurrency($currency_info['code']);
                            if ($store_total) {
                                $err = sprintf($this->language->get('error_store'), $store_total);
                            }
                        }
                        $order_total = Order::where('order_status_id', '>', 0)
                                            ->where('currency_id', '=', $id)
                                            ->count();

                        if ($order_total) {
                            $err = sprintf($this->language->get('error_order'), $order_total);
                        }

                        if (!empty($err)) {
                            $error = new AError('');
                            return $error->toJSONResponse('VALIDATION_ERROR_406', ['error_text' => $err]);
                        }

                        if ($id) {
                            Currency::destroy($id);
                        }
                    }
                }
                break;
            case 'save':
                $allowedFields =
                    array_merge(['title', 'code', 'value', 'status'], (array)$this->data['allowed_fields']);
                $ids = explode(',', $this->request->post['id']);
                if (!empty($ids)) {
                    foreach ($ids as $id) {
                        $arUpdate = [];
                        foreach ($allowedFields as $f) {

                            if ($f == 'status' && !isset($this->request->post['status'][$id])) {
                                $this->request->post['status'][$id] = 0;
                            }

                            if (isset($this->request->post[$f][$id])) {
                                $err = $this->_validateField($f, $this->request->post[$f][$id]);
                                if (!empty($err)) {
                                    $error = new AError('');
                                    return $error->toJSONResponse('VALIDATION_ERROR_406', ['error_text' => $err]);
                                }
                                $arUpdate = array_merge($arUpdate, [$f => $this->request->post[$f][$id]]);
                            }
                        }
                        Currency::find($id)->update($arUpdate);
                    }
                }

                break;

            default:
                //print_r($this->request->post);

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

        $this->loadLanguage('localisation/currency');
        if (!$this->user->canModify('listing_grid/currency')) {
            $error = new AError('');
            return $error->toJSONResponse('NO_PERMISSIONS_402',
                [
                    'error_text'  => sprintf($this->language->get('error_permission_modify'), 'listing_grid/currency'),
                    'reset_value' => true,
                ]);
        }

        if (isset($this->request->get['id'])) {
            //request sent from edit form. ID in url
            foreach ($this->request->post as $key => $value) {
                $err = $this->_validateField($key, $value);
                if (!empty($err)) {
                    $error = new AError('');
                    return $error->toJSONResponse('VALIDATION_ERROR_406', ['error_text' => $err]);
                }
                $data = [$key => $value];
                Currency::find($this->request->get['id'])->update($data);
            }
            return null;
        }

        //request sent from jGrid. ID is key of array
        $allowedFields = array_merge(['title', 'code', 'value', 'status'], (array)$this->data['allowed_fields']);
        foreach ($allowedFields as $f) {
            if (isset($this->request->post[$f])) {
                foreach ($this->request->post[$f] as $k => $v) {
                    $err = $this->_validateField($f, $v);
                    if (!empty($err)) {
                        $error = new AError('');
                        return $error->toJSONResponse('VALIDATION_ERROR_406', ['error_text' => $err]);
                    }
                    $result = Currency::find($k)->update([$f => $v]);
                    if (!$result) {
                        if ($f == 'status') {
                            $this->messages->saveNotice('Currency warning', 'Warning: You tried to disable the only enabled currency of cart!');
                        }
                        $this->response->setOutput('error!');
                        return null;
                    }
                }
            }
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    private function _validateField($field, $value)
    {
        $err = '';
        switch ($field) {
            case 'title':
                if (mb_strlen($value) < 2 || mb_strlen($value) > 32) {
                    $err = $this->language->get('error_title');
                }
                break;
            case 'code':
                if (mb_strlen($value) != 3) {
                    $err = $this->language->get('error_code');
                }
                break;
            default:
        }
        return $err;
    }

}
