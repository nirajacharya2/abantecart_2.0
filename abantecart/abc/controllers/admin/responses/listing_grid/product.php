<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2018 Belavier Commerce LLC

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
use abc\core\engine\AResource;
use abc\core\lib\AError;
use abc\core\lib\AFilter;
use abc\core\lib\AJson;
use abc\models\catalog\Category;
use abc\models\catalog\Product;
use abc\models\catalog\ProductDiscount;
use abc\models\catalog\ProductSpecial;
use H;
use stdClass;

/**
 * Class ControllerResponsesListingGridProduct
 *
 * @package abc\controllers\admin
 * @property \abc\models\admin\ModelCatalogProduct $model_catalog_product
 */
class ControllerResponsesListingGridProduct extends AController
{
    public $data = [];

    public function main()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('catalog/product');
        $this->loadModel('catalog/product');
        $this->loadModel('tool/image');

        //Clean up parameters if needed
        if (isset($this->request->get['keyword'])
            && $this->request->get['keyword'] == $this->language->get('filter_product')
        ) {
            unset($this->request->get['keyword']);
        }

        if (isset($this->request->get['pfrom']) && $this->request->get['pfrom'] == 0) {
            unset($this->request->get['pfrom']);
        }

        if (isset($this->request->get['pto'])
            && $this->request->get['pto'] == $this->language->get('filter_price_max')
        ) {
            unset($this->request->get['pto']);
        }

        //Prepare filter config
        $filter_params = [
            'category',
            'status',
            'keyword',
            'match',
            'pfrom',
            'pto'
        ];
        $grid_filter_params = ['name', 'sort_order', 'model'];

        $filter_form = new AFilter(['method' => 'get', 'filter_params' => $filter_params]);
        $filter_grid = new AFilter(['method' => 'post', 'grid_filter_params' => $grid_filter_params]);
        $data = array_merge($filter_form->getFilterData(), $filter_grid->getFilterData());

        $results = $this->model_catalog_product->getProducts($data);

        $total = $results[0]['total_num_rows'];
        $response = new stdClass();
        $response->page = $filter_grid->getParam('page');
        $response->total = $filter_grid->calcTotalPages($total);
        $response->records = $total;
        $response->userdata = new stdClass();
        $response->userdata->classes = [];

        $product_ids = [];
        foreach ($results as $result) {
            $product_ids[] = (int)$result['product_id'];
        }

        $resource = new AResource('image');
        $thumbnails = $resource->getMainThumbList(
            'products',
            $product_ids,
            $this->config->get('config_image_grid_width'),
            $this->config->get('config_image_grid_height')
        );
        $i = 0;
        foreach ($results as $result) {
            $thumbnail = $thumbnails[$result['product_id']];

            $response->rows[$i]['id'] = $result['product_id'];
            if (H::dateISO2Int($result['date_available']) > time()) {
                $response->userdata->classes[$result['product_id']] = 'warning';
            }

            if ($result['call_to_order'] > 0) {
                $price = $this->language->get('text_call_to_order');
            } else {
                $price = $this->html->buildInput(
                    [
                        'name'  => 'price['.$result['product_id'].']',
                        'value' => H::moneyDisplayFormat($result['price']),
                    ]);
            }

            $response->rows[$i]['cell'] = [
                $thumbnail['thumb_html'],
                $this->html->buildInput([
                    'name'  => 'product_description['.$result['product_id'].'][name]',
                    'value' => $result['name'],
                ]),
                $this->html->buildInput([
                    'name'  => 'model['.$result['product_id'].']',
                    'value' => $result['model'],
                ]),
                $price,
                (int)$result['quantity'],
                $this->html->buildCheckbox([
                    'name'  => 'status['.$result['product_id'].']',
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

        if (!$this->user->canModify('listing_grid/product')) {
            $error = new AError('');

            $error->toJSONResponse('NO_PERMISSIONS_402',
                [
                    'error_text'  => sprintf($this->language->get('error_permission_modify'), 'listing_grid/product'),
                    'reset_value' => true,
                ]
            );
            return null;
        }

        $this->loadModel('catalog/product');
        $this->loadLanguage('catalog/product');

        switch ($this->request->post['oper']) {
            case 'del':
                $ids = explode(',', $this->request->post['id']);
                if (!empty($ids)) {
                    $ids = array_unique($ids);
                    $this->db->beginTransaction();
                    try {
                        foreach ($ids as $id) {
                            $err = $this->validateDelete($id);
                            if (!empty($err)) {
                                $error = new AError('');
                                return $error->toJSONResponse('VALIDATION_ERROR_406', ['error_text' => $err]);
                            }
                            $product = Product::with('categories')->find($id);
                            $categories = $product->categories;

                            $product->forceDelete();
                            //run products count recalculation
                            foreach ($categories as $item) {
                                $item->touch();
                            }
                        }
                        $this->db->commit();
                    } catch (\Exception $e) {
                        $this->db->rollback();
                        $error = new AError($e->getMessage());
                        $error->toLog();
                        return $error->toJSONResponse('VALIDATION_ERROR_406', ['error_text' => $e->getMessage()]);
                    }
                }
                break;
            case 'save':
                $allowedFields = array_merge(
                    ['product_description', 'model', 'call_to_order', 'price', 'quantity', 'status'],
                    (array)$this->data['allowed_fields']
                );
                $ids = explode(',', $this->request->post['id']);
                if (!empty($ids)) {
                    foreach ($ids as $id) {
                        $upd = [];
                        foreach ($allowedFields as $f) {
                            if ($f == 'status' && !isset($this->request->post['status'][$id])) {
                                $this->request->post['status'][$id] = 0;
                            }

                            if (isset($this->request->post[$f][$id])) {
                                $err = $this->validateField($f, $this->request->post[$f][$id]);
                                if (!empty($err)) {
                                    $error = new AError('');

                                    return $error->toJSONResponse('VALIDATION_ERROR_406', ['error_text' => $err]);
                                }
                                $upd[$f] = $this->request->post[$f][$id];
                            }
                        }
                        if (!empty($upd)) {
                            Product::updateProduct($id, $upd);
                            $this->extensions->hk_ProcessData($this, 'update', ['product_id' => $id]);
                        }
                    }
                }
                break;
            case 'relate':
                $ids = explode(',', $this->request->post['id']);
                if (!empty($ids)) {
                    Product::relateProducts($ids);
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
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     */
    public function update_field()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        if (!$this->user->canModify('listing_grid/product')) {
            $error = new AError('');
            $error->toJSONResponse('NO_PERMISSIONS_402',
                [
                    'error_text'  => sprintf($this->language->get('error_permission_modify'), 'listing_grid/product'),
                    'reset_value' => true,
                ]);
            return null;
        }

        $this->loadLanguage('catalog/product');
        $this->loadModel('catalog/product');

        $product_id = (int)$this->request->get['id'];
        if ($product_id) {
            //request sent from edit form. ID in url
            foreach ($this->request->post as $key => $value) {
                $err = $this->validateField($key, $value);
                if (!empty($err)) {
                    $error = new AError('');
                    $error->toJSONResponse('VALIDATION_ERROR_406', ['error_text' => $err]);
                    return null;
                }
                if ($key == 'date_available') {
                    $value = H::dateDisplay2ISO($value);
                }
                $data = [$key => $value];
                Product::updateProduct($product_id, $data);
            }
            $this->extensions->hk_ProcessData($this, 'update_field', ['product_id' => $product_id]);
            return null;
        }

        //request sent from jGrid. ID is key of array
        $allowedFields = array_merge(
            ['product_description', 'model', 'price', 'call_to_order', 'quantity', 'status'],
            (array)$this->data['allowed_fields']
        );
        foreach ($allowedFields as $f) {
            if (isset($this->request->post[$f])) {
                foreach ($this->request->post[$f] as $k => $v) {
                    $err = $this->validateField($f, $v);
                    if (!empty($err)) {
                        $error = new AError('');
                        $error->toJSONResponse('VALIDATION_ERROR_406', ['error_text' => $err]);
                        return null;
                    }
                    Product::updateProduct($k, [$f => $v]);
                    $this->extensions->hk_ProcessData($this, 'update_field', ['product_id' => $k]);
                }
            }
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function update_discount_field()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        if (!$this->user->canModify('listing_grid/product')) {
            $error = new AError('');
            return $error->toJSONResponse('NO_PERMISSIONS_402',
                [
                    'error_text'  => sprintf($this->language->get('error_permission_modify'), 'listing_grid/product'),
                    'reset_value' => true,
                ]);
        }

        $this->loadLanguage('catalog/product');
        $this->loadModel('catalog/product');
        if (isset($this->request->get['id'])) {
            //request sent from edit form. ID in url
            foreach ($this->request->post as $key => $value) {
                $data = [$key => $value];
                $discount = ProductDiscount::find($this->request->get['id']);
                if ($discount) {
                    $discount->update($data);
                }
            }

            return null;
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
        return null;
    }

    public function update_special_field()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        if (!$this->user->canModify('listing_grid/product')) {
            $error = new AError('');

            return $error->toJSONResponse('NO_PERMISSIONS_402',
                [
                    'error_text'  => sprintf($this->language->get('error_permission_modify'), 'listing_grid/product'),
                    'reset_value' => true,
                ]);
        }

        $this->loadLanguage('catalog/product');
        $this->loadModel('catalog/product');
        if (isset($this->request->get['id'])) {
            //request sent from edit form. ID in url
            foreach ($this->request->post as $key => $value) {
                $data = [$key => $value];
                $special = ProductSpecial::find($this->request->get['id']);
                if ($special) {
                    $special->update($data);
                }
            }

            return null;
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
        return null;
    }

    public function update_relations_field()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        if (!$this->user->canModify('listing_grid/product')) {
            $error = new AError('');

            return $error->toJSONResponse('NO_PERMISSIONS_402',
                [
                    'error_text'  => sprintf($this->language->get('error_permission_modify'), 'listing_grid/product'),
                    'reset_value' => true,
                ]);
        }

        $this->loadLanguage('catalog/product');
        $this->loadModel('catalog/product');
        if (isset($this->request->get['id'])) {
            //request sent from edit form. ID in url
            foreach ($this->request->post as $key => $value) {
                $data = [$key => $value];
                Product::updateProductLinks($this->request->get['id'], $data);
            }

            return null;
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
        return null;
    }

    protected function validateField($field, $value)
    {
        $this->data['error'] = '';
        switch ($field) {
            case 'product_description' :
                if (isset($value['name']) && ((mb_strlen($value['name']) < 1) || (mb_strlen($value['name']) > 255))) {
                    $this->data['error'] = $this->language->get('error_name');
                }
                break;
            case 'model' :
                if (mb_strlen($value) > 64) {
                    $this->data['error'] = $this->language->get('error_model');
                }
                break;
            case 'keyword' :
                $this->data['error'] = $this->html->isSEOkeywordExists('product_id='.$this->request->get['id'], $value);
                break;
            case 'length' :
            case 'width'  :
            case 'height' :
            case 'weight' :
                $v = abs(H::preformatFloat($value, $this->language->get('decimal_point')));
                if ($v >= 1000) {
                    $this->data['error'] = $this->language->get('error_measure_value');
                }
                break;
        }
        $this->extensions->hk_ValidateData($this, [__FUNCTION__, $field, $value]);

        return $this->data['error'];
    }

    protected function validateDelete($id)
    {
        $this->data['error'] = '';
        $this->extensions->hk_ValidateData($this, [__FUNCTION__, $id]);

        return $this->data['error'];
    }

}
