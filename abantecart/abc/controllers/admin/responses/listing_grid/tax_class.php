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
use abc\core\lib\AFilter;
use abc\core\lib\AJson;
use stdClass;

class ControllerResponsesListingGridTaxClass extends AController
{
    public $data = [];

    public function main()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('localisation/tax_class');
        $this->loadModel('localisation/tax_class');

        //Prepare filter config
        $grid_filter_params = array_merge(['title'], (array)$this->data['grid_filter_params']);
        $filter = new AFilter(['method' => 'post', 'grid_filter_params' => $grid_filter_params]);
        $filter_data = $filter->getFilterData();

        $total = $this->model_localisation_tax_class->getTotalTaxClasses($filter_data);
        $response = new stdClass();
        $response->page = $filter->getParam('page');
        $response->total = $filter->calcTotalPages($total);
        $response->records = $total;
        $response->userdata = (object)[''];
        $results = $this->model_localisation_tax_class->getTaxClasses($filter_data);

        $i = 0;
        foreach ($results as $result) {

            $response->rows[$i]['id'] = $result['tax_class_id'];
            $response->rows[$i]['cell'] = [
                $this->html->buildInput([
                    'name'  => 'tax_class['.$result['tax_class_id'].']['.$this->session->data['content_language_id']
                        .'][title]',
                    'value' => $result['title'],
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

        $this->loadModel('localisation/tax_class');
        $this->loadLanguage('localisation/tax_class');
        if (!$this->user->canModify('listing_grid/tax_class')) {
            $error = new AError('');
            return $error->toJSONResponse('NO_PERMISSIONS_403',
                [
                    'error_text'  => sprintf($this->language->get('error_permission_modify'), 'listing_grid/tax_class'),
                    'reset_value' => true,
                ]);
        }

        switch ($this->request->post['oper']) {
            case 'del':
                $ids = explode(',', $this->request->post['id']);
                if (!empty($ids)) {
                    foreach ($ids as $id) {
                        $err = $this->_validateDelete($id);
                        if (!empty($err)) {
                            $error = new AError('');
                            return $error->toJSONResponse('VALIDATION_ERROR_406', ['error_text' => $err]);
                        }
                        $this->model_localisation_tax_class->deleteTaxClass($id);
                    }
                }
                break;
            case 'save':
                $ids = explode(',', $this->request->post['id']);
                if (!empty($ids)) {
                    foreach ($ids as $id) {
                        if (isset($this->request->post['tax_class'][$id])) {
                            foreach ($this->request->post['tax_class'][$id] as $lang => $value) {
                                if (isset($value['title'])) {
                                    $err = $this->_validateField('title', $value['title']);
                                    if (!empty($err)) {
                                        $this->response->setOutput($err);
                                        return null;
                                    }
                                }
                            }
                            $this->model_localisation_tax_class->editTaxClass($id,
                                ['tax_class' => $this->request->post['tax_class'][$id]]);
                        }
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

        $this->loadLanguage('localisation/tax_class');
        if (!$this->user->canModify('listing_grid/tax_class')) {
            $error = new AError('');
            return $error->toJSONResponse('NO_PERMISSIONS_403',
                [
                    'error_text'  => sprintf($this->language->get('error_permission_modify'), 'listing_grid/tax_class'),
                    'reset_value' => true,
                ]);
        }
        $this->loadModel('localisation/tax_class');
        if (isset($this->request->get['id'])) {
            //request sent from edit form. ID in url
            foreach ($this->request->post as $key => $value) {
                $err = '';
                if ($key == 'tax_class') {
                    foreach ($value as $lang => $val) {
                        if (isset($val['title'])) {
                            $err .= $this->_validateField('title', $val['title']);
                        }
                    }
                } else {
                    $err = $this->_validateField($key, $value);
                }
                if (!empty($err)) {
                    $error = new AError('');
                    return $error->toJSONResponse('VALIDATION_ERROR_406', ['error_text' => $err]);
                }
                $data = [$key => $value];
                $this->model_localisation_tax_class->editTaxClass($this->request->get['id'], $data);
            }
            return null;
        }

        //request sent from jGrid. ID is key of array
        if (isset($this->request->post['tax_class'])) {
            foreach ($this->request->post['tax_class'] as $id => $v) {
                foreach ($v as $lang => $value) {
                    $err = $this->_validateField('title', $value['title']);
                    if (!empty($err)) {
                        $error = new AError('');
                        return $error->toJSONResponse('VALIDATION_ERROR_406', ['error_text' => $err]);
                    }
                }
                $this->model_localisation_tax_class->editTaxClass($id, ['tax_class' => $v]);
            }
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
    public function update_rate_field()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('localisation/tax_class');
        if (!$this->user->canModify('listing_grid/tax_class')) {
            $error = new AError('');
            return $error->toJSONResponse('NO_PERMISSIONS_403',
                [
                    'error_text'  => sprintf($this->language->get('error_permission_modify'), 'listing_grid/tax_class'),
                    'reset_value' => true,
                ]);
        }

        $this->loadModel('localisation/tax_class');
        if (isset($this->request->get['id'])) {
            //request sent from edit form. ID in url
            foreach ($this->request->post as $key => $value) {
                $err = $this->_validateField($key, $value);
                if (!empty($err)) {
                    $error = new AError('');
                    return $error->toJSONResponse('VALIDATION_ERROR_406', ['error_text' => $err]);
                }
                $data = [$key => $value];
                $this->model_localisation_tax_class->editTaxRate($this->request->get['id'], $data);
            }
            return null;
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    private function _validateField($field, $value)
    {
        $this->data['error'] = '';
        switch ($field) {
            case 'title' :
                if (mb_strlen($value) < 2 || mb_strlen($value) > 128) {
                    $this->data['error'] = $this->language->get('error_tax_title');
                }
                break;
            case 'rate' :
                if (!$value) {
                    $this->data['error'] = $this->language->get('error_rate');
                }
                break;
        }

        $this->extensions->hk_ValidateData($this, __FUNCTION__, $field, $value);
        return $this->data['error'];
    }

    private function _validateDelete($tax_class_id)
    {
        $this->data['error'] = '';
        $this->loadModel('catalog/product');

        $product_total = $this->model_catalog_product->getTotalProductsByTaxClassId($tax_class_id);
        if ($product_total) {
            $this->data['error'] = sprintf($this->language->get('error_product'), $product_total);
        }

        $this->extensions->hk_ValidateData($this, __FUNCTION__, $tax_class_id);
        return $this->data['error'];
    }

    public function tax_rates()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('localisation/tax_class');
        $this->loadModel('localisation/tax_class');

        //Prepare filter config
        $grid_filter_params = array_merge(['title'], (array)$this->data['grid_filter_params']);
        $filter = new AFilter(['method' => 'post', 'grid_filter_params' => $grid_filter_params]);

        $this->loadModel('localisation/location');
        $this->loadModel('localisation/zone');
        $results = $this->model_localisation_location->getLocations();

        $zones = $locations = [];
        $zones[0] = $this->language->get('text_tax_all_zones');

        $tax_rates = $this->model_localisation_tax_class->getTaxRates($this->request->get['tax_class_id']);

        $total = sizeof($tax_rates);

        $rates = [];
        foreach ($tax_rates as $rate) {
            $rates[] = $rate['location_id'];
        }

        foreach ($results as $c) {
            if (in_array($c['location_id'], $rates)) {
                $locations[$c['location_id']] = $c['name'];
                $tmp = $this->model_localisation_zone->getZonesByLocationId($c['location_id']);
                foreach ($tmp as $zone) {
                    $zones[$zone['zone_id']] = $zone['name'];
                }
            }
        }
        unset($results, $tmp);

        $response = new stdClass();
        $response->page = $filter->getParam('page');
        $response->total = $filter->calcTotalPages($total);
        $response->records = $total;
        $response->userdata = (object)[''];

        foreach ($tax_rates as $i => $tax_rate) {
            $response->rows[$i]['id'] = $tax_rate['tax_rate_id'];
            $response->rows[$i]['cell'] = [
                $locations[$tax_rate['location_id']],
                $zones[(int)$tax_rate['zone_id']],
                $tax_rate['description'],
                $tax_rate['rate_prefix'].$tax_rate['rate'],
                $tax_rate['priority'],
            ];
        }
        $this->data['response'] = $response;

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
        $this->load->library('json');
        $this->response->setOutput(AJson::encode($this->data['response']));
    }
}