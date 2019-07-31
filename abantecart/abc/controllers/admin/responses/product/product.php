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

use abc\core\ABC;
use abc\core\engine\AController;
use abc\core\engine\AForm;
use abc\core\lib\ACurrency;
use abc\core\lib\APromotion;
use abc\core\engine\AResource;
use abc\core\engine\HtmlElementFactory;
use abc\core\lib\AError;
use abc\core\lib\AJson;
use abc\core\lib\ATax;
use abc\core\lib\AWeight;
use abc\core\lib\contracts\AttributeManagerInterface;
use abc\models\admin\ModelCatalogCategory;
use abc\models\admin\ModelCatalogDownload;
use abc\models\catalog\Category;
use abc\models\catalog\Product;
use abc\models\order\Order;
use abc\models\order\OrderProduct;
use abc\models\system\Setting;
use H;

/**
 * Class ControllerResponsesProductProduct
 *
 * @package abc\controllers\admin
 * @property ModelCatalogCategory $model_catalog_category
 * @property ModelCatalogDownload $model_catalog_download
 */
class ControllerResponsesProductProduct extends AController
{
    public $error = [];
    public $data = [];
    /**
     * @var AttributeManagerInterface
     */
    protected $attribute_manager;

    public function products()
    {
        $products_data = [];
        $post =& $this->request->post;
        $get =& $this->request->get;
        $exclude = (array)$post['exclude'];
        if (isset($get['exclude'])) {
            $get['exclude'] = (array)$get['exclude'];
            $exclude = array_merge($get['exclude'], $exclude);
        }
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->loadModel('catalog/product');
        if (isset($post['coupon_product'])) {
            $products = $post['coupon_product'];
            foreach ($products as $product_id) {
                $product_info = $this->model_catalog_product->getProduct($product_id);
                if ($product_info) {
                    $products_data[] = [
                        'id'         => $product_info['product_id'],
                        'name'       => $product_info['name'],
                        'meta'       => $product_info['model'],
                        'sort_order' => (int)$product_info['sort_order'],
                    ];
                }
            }
        } else {
            if (isset($post['term'])) {
                $filter = [
                    'limit'               => 20,
                    'content_language_id' => $this->language->getContentLanguageID(),
                    'filter'              => [
                        'keyword' => $post['term'],
                        'match'   => 'all',
                        'exclude' => ['product_id' => $exclude],
                    ],
                ];
                //if need to show only available products
                if ($this->request->post['filter'] == 'enabled_only') {
                    $filter['filter']['status'] = 1;
                    $filter['subsql_filter'] = 'date_available<=NOW()';
                }
                $products = $this->model_catalog_product->getProducts($filter);

                $product_ids = [];
                foreach ($products as $result) {
                    $product_ids[] = (int)$result['product_id'];
                }

                $resource = new AResource('image');
                $thumbnails = $resource->getMainThumbList(
                    'products',
                    $product_ids,
                    $this->config->get('config_image_grid_width'),
                    $this->config->get('config_image_grid_height')
                );

                foreach ($products as $product_data) {
                    $thumbnail = $thumbnails[$product_data['product_id']];

                    if ($get['currency_code']) {
                        $price = round(
                            $this->currency->convert(
                                $product_data['price'],
                                $this->config->get('config_currency'),
                                $get['currency_code']
                            ), 2);
                    } else {
                        $price = $product_data['price'];
                    }

                    $formatted_price = $this->currency->format(
                                            $product_data['price'],
                                            ($get['currency_code']
                                            ? $get['currency_code']
                                            : $this->config->get('config_currency') )
                    );

                    $products_data[] = [
                        'image'      => $thumbnail['thumb_html'],
                        'id'         => $product_data['product_id'],
                        'name'       => $product_data['name'].' - '.$formatted_price,
                        'price'      => $price,
                        'meta'       => $product_data['model'],
                        'sort_order' => (int)$product_data['sort_order'],
                    ];
                }
            }
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->load->library('json');
        $this->response->addJSONHeader();
        $this->response->setOutput(AJson::encode($products_data));
    }

    public function update()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        if (!$this->user->canModify('product/product')) {
            $error = new AError('');
            return $error->toJSONResponse('NO_PERMISSIONS_402',
                [
                    'error_text'  => sprintf($this->language->get('error_permission_modify'), 'product/product'),
                    'reset_value' => true,
                ]);
        }

        $this->loadLanguage('catalog/product');
        $this->loadModel('catalog/product');

        $result = '';

        if ($this->request->is_POST()) {
            $this->model_catalog_product->updateProduct($this->request->get['product_id'], $this->request->post);
            $result = 'Saved!';
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->response->setOutput($result);
    }

    public function category()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadModel('catalog/product');
        /**
         * @var APromotion $promotion
         */
        $promotion = ABC::getObjectByAlias('APromotion',[$this->request->get['customer_group_id']]);

        if (isset($this->request->get['category_id'])) {
            $category_id = $this->request->get['category_id'];
        } else {
            $category_id = 0;
        }

        $product_data = [];
        $results = $this->model_catalog_product->getProductsByCategoryId($category_id);
        foreach ($results as $result) {

            $discount = $promotion->getProductDiscount($result['product_id']);
            if ($discount) {
                $price = $discount;
            } else {
                $price = $result['price'];
                $special = $promotion->getProductSpecial($result['product_id']);
                if ($special) {
                    $price = $special;
                }
            }

            if (!empty($this->request->get['currency']) && !empty($this->request->get['value'])) {
                $price = $this->currency->format(
                                            (float)$price,
                                            $this->request->get['currency'],
                                            $this->request->get['value']
                );
            } else {
                $price = $this->currency->format((float)$price);
            }

            $product_data[] = [
                                'product_id' => $result['product_id'],
                                'name'       => $result['name'],
                                'model'      => $result['model'],
                                'price'      => $price,
            ];
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->load->library('json');
        $this->response->addJSONHeader();
        $this->response->setOutput(AJson::encode($product_data));
    }

    public function product_categories()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadModel('catalog/category');

        if (isset($this->request->post['id'])) { // variant for popup listing
            $categories = $this->request->post['id'];
        } else {
            $categories = [];
        }
        $category_data = [];

        foreach ($categories as $category_id) {
            $category_data[] = [
                'id'         => $category_id,
                'name'       => (new Category())->getPath($category_id),
                'sort_order' => 0,
            ];

        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->load->library('json');
        $this->response->addJSONHeader();
        $this->response->setOutput(AJson::encode($category_data));
    }

    public function related()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadModel('catalog/product');

        if (isset($this->request->post['product_related'])) {
            $products = $this->request->post['product_related'];
        } elseif (isset($this->request->post['id'])) { // variant for popup listing
            $products = $this->request->post['id'];
        } else {
            $products = [];
        }
        $product_data = [];

        foreach ($products as $product_id) {
            $product_info = $this->model_catalog_product->getProduct($product_id);

            if ($product_info) {
                $product_data[] = [
                    'id'         => $product_info['product_id'],
                    'product_id' => $product_info['product_id'],
                    'name'       => $product_info['name'],
                    'model'      => $product_info['model'],
                    'sort_order' => 0,
                ];
            }
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->load->library('json');
        $this->response->addJSONHeader();
        $this->response->setOutput(AJson::encode($product_data));
    }

    public function get_options_list()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadModel('catalog/product');
        $product_options = $this->model_catalog_product->getProductOptions($this->request->get['product_id']);

        $result = [];
        foreach ($product_options as $option) {
            $option_name = trim($option['language'][$this->language->getContentLanguageID()]['name']);
            $result[$option['product_option_id']] = $option_name ? $option_name : 'n/a';
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->load->library('json');
        $this->response->addJSONHeader();
        $this->response->setOutput(AJson::encode($result));
    }

    public function update_option()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        if (!$this->user->canModify('product/product')) {
            $error = new AError('');
            return $error->toJSONResponse('NO_PERMISSIONS_402',
                [
                    'error_text'  => sprintf($this->language->get('error_permission_modify'), 'product/product'),
                    'reset_value' => true,
                ]);
        }
        //needs to validate attribute properties
        // first - prepare data for validation
        if (!isset($this->request->get['required'])) {
            $this->request->get['required'] = 0;
        }

        if (H::has_value($this->request->get['regexp_pattern'])) {
            $this->request->get['regexp_pattern'] = trim($this->request->get['regexp_pattern']);
        }
        if (H::has_value($this->request->get['option_placeholder'])) {
            $this->request->get['option_placeholder'] = trim($this->request->get['option_placeholder']);
        }

        $this->loadModel('catalog/product');

        $data = $this->request->get;
        /**
         * @var AttributeManagerInterface $attribute_manager
         */
        $attribute_manager = ABC::getObjectByAlias('AttributeManager',['product_option']);
        $option_info = $this->model_catalog_product->getProductOption(
                                                        $this->request->get['product_id'],
                                                        $this->request->get['option_id']
        );
        $data['element_type'] = $option_info['element_type'];
        $data['attribute_type_id'] = $attribute_manager->getAttributeTypeID('product_option');

        $errors = $attribute_manager->validateAttributeCommonData($data);

        if (!$errors) {
            $this->model_catalog_product->updateProductOption($this->request->get['option_id'], $this->request->get);
        } else {
            $error = new AError('');
            return $error->toJSONResponse('', ['error_title' => implode('<br>', $errors)]);
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function load_option()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('catalog/product');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->loadModel('catalog/product');

        $this->view->assign('success', $this->session->data['success']);
        unset($this->session->data['success']);

        $product_id = (int)$this->request->get['product_id'];
        $option_id = (int)$this->request->get['option_id'];

        $this->data['option_data'] = $this->model_catalog_product->getProductOption($product_id, $option_id);

        if ($this->data['option_data']) {

            $language_id = $this->language->getContentLanguageID();
            $this->data['language_id'] = $language_id;

            $this->data['element_types'] = HtmlElementFactory::getAvailableElements();
            $this->data['elements_with_options'] = HtmlElementFactory::getElementsWithOptions();
            $this->data['selectable'] = in_array(
                                            $this->data['option_data']['element_type'],
                                            $this->data['elements_with_options']
            );

            $this->data['option_type']
                = $this->data['element_types'][$this->data['option_data']['element_type']]['type'];

            $this->attribute_manager = ABC::getObjectByAlias('AttributeManager', ['product_option']);

            $this->data['action'] = $this->html->getSecureURL(
                'product/product/update_option_values',
                '&product_id='.$product_id.'&option_id='.$option_id);

            $this->data['option_values'] = $this->model_catalog_product->getProductOptionValues(
                                                                                                $product_id,
                                                                                                $option_id
            );

            $this->data['option_name'] = $this->html->buildElement([
                'type'  => 'input',
                'name'  => 'name',
                'value' => $this->data['option_data']['language'][$language_id]['name'],
                'style' => 'medium-field',
            ]);

            if (in_array($this->data['option_data']['element_type'],
                HtmlElementFactory::getElementsWithPlaceholder())) {
                $this->data['option_placeholder'] = $this->html->buildElement([
                    'type'  => 'input',
                    'name'  => 'option_placeholder',
                    'value' => $this->data['option_data']['language'][$language_id]['option_placeholder'],
                    'style' => 'medium-field',
                ]);
            }

            $this->data['status'] = $this->html->buildElement([
                'type'  => 'checkbox',
                'name'  => 'status',
                'value' => $this->data['option_data']['status'],
                'style' => 'btn_switch btn-group-xs',
            ]);
            $this->data['option_sort_order'] = $this->html->buildElement([
                'type'  => 'input',
                'name'  => 'sort_order',
                'value' => $this->data['option_data']['sort_order'],
                'style' => 'tiny-field',
            ]);
            $this->data['required'] = $this->html->buildElement([
                'type'  => 'checkbox',
                'name'  => 'required',
                'value' => $this->data['option_data']['required'],
                'style' => 'btn_switch btn-group-xs',
            ]);
            //for file-option
            if ($this->data['option_data']['element_type'] == 'U') {
                $option_settings = unserialize($this->data['option_data']['settings']);

                $this->data['extensions'] = $this->html->buildElement([
                    'type'  => 'input',
                    'name'  => 'settings[extensions]',
                    'value' => $option_settings['extensions'],
                    'style' => 'no-save',
                ]);

                $this->data['min_size'] = $this->html->buildElement([
                    'type'  => 'input',
                    'name'  => 'settings[min_size]',
                    'value' => $option_settings['min_size'],
                    'style' => 'small-field no-save',
                ]);
                $this->data['max_size'] = $this->html->buildElement([
                    'type'  => 'input',
                    'name'  => 'settings[max_size]',
                    'value' => $option_settings['max_size'],
                    'style' => 'small-field no-save',
                ]);
                $this->data['directory'] = $this->html->buildElement([
                    'type'  => 'input',
                    'name'  => 'settings[directory]',
                    'value' => $option_settings['directory'],
                    'style' => 'no-save',
                ]);

                $this->data['entry_upload_dir'] = sprintf(
                                                    $this->language->get('entry_upload_dir'),
                                                    'admin/system/uploads/'
                );
            } else {
                $this->data['option_regexp_pattern'] = $this->html->buildElement([
                    'type'  => 'input',
                    'name'  => 'regexp_pattern',
                    'value' => $this->data['option_data']['regexp_pattern'],
                    'style' => 'medium-field',
                ]);

                $this->data['option_error_text'] = $this->html->buildElement([
                    'type'  => 'input',
                    'name'  => 'error_text',
                    'value' => $this->data['option_data']['language'][$language_id]['error_text'],
                    'style' => 'medium-field',
                ]);
            }

            $this->data['remove_option'] = $this->html->getSecureURL(
                                                'product/product/del_option',
                                                '&product_id='.$product_id.'&option_id='.$option_id);

            $this->data['button_remove_option'] = $this->html->buildElement([
                'type'  => 'button',
                'text'  => $this->language->get('button_remove_option'),
                'style' => 'button3',
                'href'  => $this->data['remove_option'],
            ]);
            $this->data['button_save'] = $this->html->buildElement([
                'type'  => 'button',
                'text'  => $this->language->get('button_save'),
                'style' => 'button1',
            ]);
            $this->data['button_reset'] = $this->html->buildElement([
                'type'  => 'button',
                'text'  => $this->language->get('button_reset'),
                'style' => 'button2',
            ]);

            $this->data['update_option_values'] = $this->html->getSecureURL(
                                                    'product/product/update_option_values',
                                                    '&product_id='.$product_id.'&option_id='.$option_id);

            // form of option values list
            $form = new AForm('HT');
            $form->setForm(
                [
                    'form_name' => 'update_option_values',
                ]
            );
            $this->data['form']['id'] = 'update_option_values';
            $this->data['update_option_values_form']['open'] = $form->getFieldHtml(
                [
                    'type'   => 'form',
                    'name'   => 'update_option_values',
                    'attr'   => 'data-confirm-exit="true" class="form-horizontal"',
                    'action' => $this->data['update_option_values'],
                ]
            );

            //form of option
            $form = new AForm('HT');
            $form->setForm([
                'form_name' => 'option_value_form',
            ]);

            $this->data['form']['id'] = 'option_value_form';
            $this->data['form']['form_open'] = $form->getFieldHtml([
                'type'   => 'form',
                'name'   => 'option_value_form',
                'attr'   => 'data-confirm-exit="true"',
                'action' => $this->data['update_option_values'],
            ]);

            //Load option values rows
            foreach ($this->data['option_values'] as $key => $item) {
                $this->request->get['product_option_value_id'] = $item['product_option_value_id'];
                $this->data['option_values'][$key]['row'] = $this->_option_value_form($form);
            }

            $this->data['new_option_row'] = '';
            if (in_array($this->data['option_data']['element_type'], $this->data['elements_with_options'])) {
                $this->request->get['product_option_value_id'] = null;
                $this->data['new_option_row'] = $this->_option_value_form($form);
            }

            $this->view->batchAssign($this->data);
        }
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
        if ($this->data['option_data']) {
            $this->processTemplate('responses/product/option_values.tpl');
        } else {
            $this->response->setOutput('');
        }
    }

    public function del_option()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        if (!$this->user->canModify('product/product')) {
            $error = new AError('');
            return $error->toJSONResponse('NO_PERMISSIONS_402',
                [
                    'error_text'  => sprintf($this->language->get('error_permission_modify'), 'product/product'),
                    'reset_value' => true,
                ]);
        }

        $this->loadLanguage('catalog/product');
        $this->loadModel('catalog/product');
        $this->model_catalog_product->deleteProductOption(
                                            $this->request->get['product_id'],
                                            $this->request->get['option_id']
        );
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
        $this->response->setOutput($this->language->get('text_option_removed'));
    }

    public function update_option_values()
    {
        if (!$this->user->canModify('product/product')) {
            $error = new AError('');
            return $error->toJSONResponse('NO_PERMISSIONS_402',
                [
                    'error_text'  => sprintf($this->language->get('error_permission_modify'), 'product/product'),
                    'reset_value' => true,
                ]);
        }

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('catalog/product');
        $this->loadModel('catalog/product');

        $option_info = $this->model_catalog_product->getProductOption(
            $this->request->get['product_id'],
            $this->request->get['option_id']
        );

        //remove html-code from textarea product option
        if (in_array($option_info['element_type'], ['T', 'B'])) {
            foreach ((array)$this->request->post['name'] as &$v) {
                $v = strip_tags(html_entity_decode($v, ENT_QUOTES, ABC::env('APP_CHARSET')));
                $v = str_replace('\r\n', "\n", $v);
            }
        }

        $this->model_catalog_product->updateProductOptionValues(
            $this->request->get['product_id'],
            $this->request->get['option_id'],
            $this->request->post
        );
        $this->session->data['success'] = $this->language->get('text_success_option');

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        abc_redirect($this->html->getSecureURL(
            'product/product/load_option',
            '&product_id='.$this->request->get['product_id'].'&option_id='.$this->request->get['option_id'])
        );
    }

    /**
     * @param $form AForm
     *
     * @return string
     * @throws \Exception
     */
    private function _option_value_form($form)
    {
        $this->data['option_attribute'] = $this->attribute_manager->getAttributeByProductOptionId($this->request->get['option_id']);
        $this->data['option_attribute']['values'] = [];

        $this->data['option_attribute']['type'] = 'input';
        $product_option_value_id = $this->request->get['product_option_value_id'];
        $group_attribute = [];
        if ($this->data['option_attribute']['attribute_id']) {
            $group_attribute = $this->attribute_manager->getAttributes([], $this->data['language_id'],
                $this->data['option_attribute']['attribute_id']);
        }

        $this->data['elements_with_options'] = HtmlElementFactory::getElementsWithOptions();
        //load values for attributes with options
        if (count($group_attribute)) {
            $this->data['option_attribute']['group'] = [];
            foreach ($group_attribute as $attribute) {
                $option_id = $attribute['attribute_id'];
                $this->data['option_attribute']['group'][$option_id]['name'] = $attribute['name'];
                $this->data['option_attribute']['group'][$option_id]['type'] = 'hidden';
                if (in_array($attribute['element_type'], $this->data['elements_with_options'])) {
                    $this->data['option_attribute']['group'][$option_id]['type'] = 'selectbox';
                    $values = $this->attribute_manager->getAttributeValues(
                        $attribute['attribute_id'],
                        $this->language->getContentLanguageID()
                    );

                    foreach ($values as $v) {
                        $this->data['option_attribute']['group'][$option_id]['values'][$v['attribute_value_id']] =
                            addslashes(html_entity_decode($v['value'], ENT_COMPAT, ABC::env('APP_CHARSET')));
                    }
                }
            }

        } else {
            if (in_array($this->data['option_attribute']['element_type'], $this->data['elements_with_options'])) {
                $this->data['option_attribute']['type'] = 'selectbox';
                if (is_null($product_option_value_id)) { // for new row values
                    $values = $this->attribute_manager->getAttributeValues(
                        $this->data['option_attribute']['attribute_id'],
                        $this->language->getContentLanguageID()
                    );
                } else {
                    $values = $this->getProductOptionValues(
                        $this->data['option_attribute']['attribute_id'],
                        $this->language->getContentLanguageID()
                    );
                }

                foreach ($values as $v) {
                    $this->data['option_attribute']['values'][$v['attribute_value_id']] =
                        addslashes(html_entity_decode($v['value'], ENT_COMPAT, ABC::env('APP_CHARSET')));
                }
            }

        }

        $this->data['cancel'] = $this->html->getSecureURL(
            'product/product/load_option',
            '&product_id='.$this->request->get['product_id'].'&option_id='.$this->request->get['option_id']
        );

        if (isset($this->request->get['product_option_value_id'])) {
            $this->data['row_id'] = 'row'.$product_option_value_id;
            $this->data['attr_val_id'] = $product_option_value_id;
            $item_info = $this->model_catalog_product->getProductOptionValue(
                $this->request->get['product_id'],
                $product_option_value_id
            );
        } else {
            $this->data['row_id'] = 'new_row';
        }

        $fields = [
            'default',
            'name',
            'sku',
            'quantity',
            'subtract',
            'price',
            'prefix',
            'sort_order',
            'weight',
            'weight_type',
            'attribute_value_id',
            'children_options',
        ];
        foreach ($fields as $f) {
            if (isset($this->request->post[$f])) {
                $this->data[$f] = $this->request->post[$f];
            } elseif (isset($item_info)) {
                $this->data[$f] = $item_info[$f];
            } else {
                $this->data[$f] = '';
            }
        }

        if (isset($this->request->post['name'])) {
            $this->data['name'] = $this->request->post['name'];
        } elseif (isset($item_info)) {
            $this->data['name'] = $item_info['language'][$this->language->getContentLanguageID()]['name'];
        }

        if (isset($this->data['option_attribute']['group'])) {
            //process grouped (parent/child) options
            $this->data['form']['fields']['option_value'] = '';
            foreach ($this->data['option_attribute']['group'] as $attribute_id => $data) {
                $this->data['form']['fields']['option_value'] .=
                    '<span style="white-space: nowrap;">'.$data['name'].''.$form->getFieldHtml([
                        'type'    => $data['type'],
                        'name'    => 'attribute_value_id['.$product_option_value_id.']['.$attribute_id.']',
                        'value'   => $this->data['children_options'][$attribute_id],
                        'options' => $data['values'],
                        'attr'    => '',
                    ]).'<span><br class="clr_both">';

            }
        } else {
            if (in_array($this->data['option_attribute']['element_type'], $this->data['elements_with_options'])) {
                $this->data['form']['fields']['option_value'] = $form->getFieldHtml([
                    'type'    => $this->data['option_attribute']['type'],
                    'name'    => 'attribute_value_id['.$product_option_value_id.']',
                    'value'   => $this->data['attribute_value_id'],
                    'options' => $this->data['option_attribute']['values'],
                ]);
            } else {
                if ($this->data['option_attribute']['element_type'] == 'U') {
                    //for file there is no option value
                    $this->data['form']['fields']['option_value'] = '';
                } else {

                    $arr = [
                        'type'  => in_array($this->data['option_data']['element_type'], ['T', 'B'])
                                    ? 'textarea'
                                    : 'input',
                        'name'  => 'name['.$product_option_value_id.']',
                        'value' => $this->data['name'],
                    ];
                    // for checkbox show error when value is empty
                    if ($this->data['option_data']['element_type'] == 'C' && $this->data['name'] == '') {
                        $arr['style'] = 'alert-danger';
                    }

                    $this->data['form']['fields']['option_value'] = $form->getFieldHtml($arr);

                }
            }
        }

        $this->data['form']['fields']['product_option_value_id'] = $form->getFieldHtml([
            'type'  => 'hidden',
            'name'  => 'product_option_value_id['.$product_option_value_id.']',
            'value' => $product_option_value_id,
        ]);

        if (in_array($this->data['option_data']['element_type'], $this->data['elements_with_options'])) {
            $this->data['form']['fields']['default'] = $form->getFieldHtml([
                'type'    => 'radio',
                'name'    => 'default_value',
                'id'      => 'default_'.$product_option_value_id,
                'value'   => ($this->data['default'] ? $product_option_value_id : ''),
                'options' => [$product_option_value_id => ''],
            ]);
            $this->data['with_default'] = 1;
        }

        $this->data['form']['fields']['sku'] = $form->getFieldHtml([
            'type'  => 'input',
            'name'  => 'sku['.$product_option_value_id.']',
            'value' => $this->data['sku'],
        ]);
        $this->data['form']['fields']['quantity'] = $form->getFieldHtml([
            'type'  => 'input',
            'name'  => 'quantity['.$product_option_value_id.']',
            'value' => $this->data['quantity'],
            'style' => 'small-field',
        ]);
        $this->data['form']['fields']['subtract'] = $form->getFieldHtml([
            'type'    => 'selectbox',
            'name'    => 'subtract['.$product_option_value_id.']',
            'value'   => $this->data['subtract'],
            'options' => [
                            1 => $this->language->get('text_yes'),
                            0 => $this->language->get('text_no'),
            ],
        ]);
        $this->data['form']['fields']['price'] = $form->getFieldHtml(
            [
                'type'  => 'input',
                'name'  => 'price['.$product_option_value_id.']',
                'value' => H::moneyDisplayFormat($this->data['price']),
                'style' => 'medium-field',
            ]
        );

        $this->data['prefix'] = trim($this->data['prefix']);
        $currency_symbol = $this->currency->getCurrency($this->config->get('config_currency'));
        $currency_symbol = $currency_symbol['symbol_left'].$currency_symbol['symbol_right'];
        if (!$this->data['prefix']) {
            $this->data['prefix'] = $currency_symbol;
        }

        $this->data['form']['fields']['prefix'] = $form->getFieldHtml([
            'type'    => 'selectbox',
            'name'    => 'prefix['.$product_option_value_id.']',
            'value'   => $this->data['prefix'],
            'options' => [
                            '$' => $currency_symbol,
                            '%' => '%',
            ],
            'style'   => 'small-field',
        ]);
        $this->data['form']['fields']['sort_order'] = $form->getFieldHtml([
            'type'  => 'input',
            'name'  => 'sort_order['.$product_option_value_id.']',
            'value' => $this->data['sort_order'],
            'style' => 'small-field',
        ]);
        $this->data['form']['fields']['weight'] = $form->getFieldHtml([
            'type'  => 'input',
            'name'  => 'weight['.$product_option_value_id.']',
            'value' => $this->data['weight'],
            'style' => 'small-field',
        ]);

        //build available weight units for options
        $wht_options = ['%' => '%'];
        $this->loadModel('localisation/weight_class');
        $selected_unit = trim($this->data['weight_type']);
        $prd_info = $this->model_catalog_product->getProduct($this->request->get['product_id']);
        $prd_weight_info = $this->model_localisation_weight_class->getWeightClass($prd_info['weight_class_id']);
        $wht_options[$prd_weight_info['unit']] = $prd_weight_info['title'];

        $option_weight_class_id = '';
        if ($selected_unit && $selected_unit != '%') {
            $a_weight = new AWeight($this->registry);
            $option_weight_class_id = $a_weight->getClassIDByUnit($selected_unit);
        }

        if (empty($selected_unit)) {
            //no weight yet, use product weight unit as default
            $selected_unit = trim($prd_weight_info['unit']);
        } else {
            if ($option_weight_class_id != trim($prd_info['weight_class_id']) && $selected_unit != '%') {
                //main product type has changed. Show what weight unit we have in option
                $weight_info = $this->model_localisation_weight_class->getWeightClassDescriptionByUnit($selected_unit);
                $wht_options[$selected_unit] = $weight_info['title'];
            }
        }
        $this->data['form']['fields']['weight_type'] = $form->getFieldHtml([
            'type'    => 'selectbox',
            'name'    => 'weight_type['.$product_option_value_id.']',
            'value'   => $selected_unit,
            'options' => $wht_options,
        ]);

        $resources_html = $this->dispatch('responses/common/resource_library/get_resources_html');
        $this->data['resources_html'] = $resources_html->dispatchGetOutput();

        $this->extensions->hk_ProcessData($this, 'option_value_row');
        $this->view->batchAssign($this->data);
        return $this->view->fetch('responses/product/option_value_row.tpl');
    }

    /**
     * @param int $attribute_id
     * @param int $language_id
     *
     * @return array
     * @throws \Exception
     */
    public function getProductOptionValues($attribute_id, $language_id = 0)
    {
        if (!$language_id) {
            $language_id = $this->language->getContentLanguageID();
        }
        $query = $this->db->query(
            "SELECT ga.*, gad.value, pov.product_option_value_id
            FROM ".$this->db->table_name("global_attributes_values")." ga
                LEFT JOIN ".$this->db->table_name("global_attributes_value_descriptions")." gad
                ON ( ga.attribute_value_id = gad.attribute_value_id AND gad.language_id = '".(int)$language_id."' )
            LEFT JOIN `".$this->db->table_name('product_option_values')."` pov 
                ON pov.attribute_value_id = ga.attribute_value_id
            WHERE ga.attribute_id = '".$this->db->escape($attribute_id)."'
            ORDER BY sort_order"
        );
        return $query->rows;
    }

    public function processDownloadForm()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        if (!$this->user->canModify('product/product')) {
            $error = new AError('');
            return $error->toJSONResponse('NO_PERMISSIONS_402',
                [
                    'error_text'  => sprintf($this->language->get('error_permission_modify'), 'product/product'),
                    'reset_value' => true,
                ]);
        }

        if (!$this->request->is_POST()) {
            return null;
        }

        $this->loadModel('catalog/download');
        if ($this->_validateDownloadForm($this->request->post)) {
            $post_data = $this->request->post;

            //disable download if file not set
            if (!$post_data['filename']) {
                $post_data['status'] = 0;
            }
            // for shared downloads
            if (!isset($post_data['shared']) && !$this->request->get['product_id']) {
                $post_data['shared'] = 1;
            }

            if ($post_data['mask']) {
                $post_data['mask'] = str_replace(' ', '_', $post_data['mask']);
            }

            if ((int)$this->request->get['download_id']) {
                $this->model_catalog_download->editDownload($this->request->get['download_id'], $post_data);
                $download_id = (int)$this->request->get['download_id'];
            } else {
                $post_data['product_id'] = (int)$this->request->get['product_id'];
                $download_id = $this->model_catalog_download->addDownload($post_data);
                $this->session->data['success'] = $this->language->get('text_success_download_save');
            }

            $this->data['output'] = [
                'download_id' => $download_id,
                'success'     => true,
                'result_text' => $this->language->get('text_success'),
            ];

        } else {
            $error = new AError('');
            $err_data = ['error_text' => $this->error];
            return $error->toJSONResponse('VALIDATION_ERROR_406', $err_data);
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->response->addJSONHeader();
        $this->load->library('json');
        $this->response->setOutput(AJson::encode($this->data['output']));
    }

    public function buildDownloadForm()
    {
        $this->data = [];
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('catalog/files');
        $this->loadModel('localisation/order_status');
        $this->loadModel('catalog/download');

        $this->data['download_id'] = $download_id = $this->request->get['download_id'];
        $this->data['product_id'] = $product_id = (int)$this->request->get['product_id'];

        // for new download - create form for mapping shared downloads to product
        if (!$download_id && $product_id) {
            $this->_buildSelectForm($product_id);
        }

        // CREATE NEW PRODUCT FILE
        if ($download_id) {
            $form = new AForm('HS');
            $this->data['update'] =
                $this->html->getSecureURL('listing_grid/download/update_field', '&id='.$download_id);
            $this->data['action'] =
                $this->html->getSecureURL('r/product/product/processDownloadForm', '&download_id='.$download_id);
        } else {
            $form = new AForm('HT');
            $this->data['action'] = $this->html->getSecureURL(
                                                    'r/product/product/processDownloadForm',
                                                    '&product_id='.$product_id
            );
        }
        $form->setForm([
            'form_name' => 'downloadFrm',
            'update'    => $this->data['update'],
        ]);

        $this->_buildGeneralSubform($form, $download_id, $product_id);

        // DOWNLOAD ATTRIBUTES PIECE OF FORM
        $this->_buildAttributesSubform($form);

        $this->data['form_title'] = $download_id
                                    ? $this->language->get('text_edit_product_file')
                                    : $this->language->get('text_create_download');
        if ($product_id) {
            $this->data['file_list_url'] = $this->html->getSecureURL(
                                                    'catalog/product_files',
                                                    '&product_id='.$product_id
            );
        } else {
            $this->data['file_list_url'] = $this->html->getSecureURL('catalog/download');
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->view->batchAssign($this->data);
        $this->processTemplate('responses/product/product_file_form.tpl');
    }

    /**
     * @param int $product_id
     *
     * @throws \Exception
     */
    private function _buildSelectForm($product_id)
    {
        $shared_downloads = $this->model_catalog_download->getSharedDownloads();
        $options = [];
        foreach ($shared_downloads as $d) {
            $options[$d['download_id']] = $d['name'];
        }

        $product_downloads = $this->model_catalog_download->getProductDownloadsDetails($product_id);
        $shd = array_keys($options);
        foreach ($product_downloads as $d) {
            if (in_array($d['download_id'], $shd)) {
                unset($options[$d['download_id']]);
            }
        }

        if ($options) {
            $form0 = new AForm('ST');
            $form0->setForm([
                'form_name' => 'SharedFrm',
                'update'    => $this->data['update'],
            ]);
            $this->data['form0']['form_open'] = $form0->getFieldHtml([
                'type'   => 'form',
                'name'   => 'SharedFrm',
                'attr'   => 'data-confirm-exit="true" class="aform form-horizontal"',
                'action' => $this->html->getSecureURL('catalog/product_files', '&product_id='.$product_id),
            ]);

            $this->data['form0']['shared'] = $form0->getFieldHtml([
                'type'        => 'checkboxgroup',
                'name'        => 'selected[]',
                'value'       => $this->data['download_id'],
                'options'     => $options,
                'style'       => 'chosen',
                'placeholder' => $this->language->get('text_select'),
            ]);

            $this->data['form0']['submit'] = $form0->getFieldHtml([
                'type' => 'button',
                'name' => 'submit',
                'text' => $this->language->get('text_add'),
            ]);
        }

    }

    /**
     * @param AForm $form
     * @param int $download_id
     * @param int $product_id
     *
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     */
    private function _buildGeneralSubform($form, $download_id, $product_id)
    {
        if ($download_id) {
            $file_data = $this->model_catalog_download->getDownload($download_id);
            $this->_validateDownloadForm($file_data);
            $this->data['error'] = $this->error;
        } else {
            $file_data = [];
            $file_data['status'] = 1; //set status ON for new download by default
        }

        $this->data['form']['form_open'] = $form->getFieldHtml([
            'type'   => 'form',
            'name'   => 'downloadFrm',
            'attr'   => 'data-confirm-exit="true" class="aform form-horizontal"',
            'action' => $this->data['action'],
        ]);
        $this->data['form']['submit'] = $form->getFieldHtml([
            'type'  => 'button',
            'name'  => 'submit',
            'text'  => ((int)$download_id ? $this->language->get('button_save') : $this->language->get('text_add')),
            'style' => 'button1',
        ]);

        $this->data['form']['cancel'] = $form->getFieldHtml([
            'type'  => 'button',
            'name'  => 'cancel',
            'href'  => $this->html->getSecureURL('catalog/product_files', '&product_id='.$product_id),
            'text'  => $this->language->get('button_cancel'),
            'style' => 'button2',
        ]);

        $order_statuses = $this->model_localisation_order_status->getOrderStatuses();

        $this->data['date_added'] = H::dateISO2Display(
                                            $file_data['date_added'],
                                            $this->language->get('date_format_short').' '.$this->language->get('time_format')
        );
        $this->data['date_modified'] = H::dateISO2Display(
                                            $file_data['date_modified'],
                                            $this->language->get('date_format_short').' '.$this->language->get('time_format')
        );

        $this->data['action'] = $this->html->getSecureURL(
                                                'r/product/product/processDownloadForm',
                                                '&product_id='.$product_id
        );

        $resources_scripts = $this->dispatch(
            'responses/common/resource_library/get_resources_scripts',
            [
                'object_name' => 'downloads',
                'object_id'   => '',
                'types'       => ['download'],
            ]);
        $this->data['resources_scripts'] = $resources_scripts->dispatchGetOutput();

        $this->data['form']['fields']['general']['resource'] = $form->getFieldHtml($props[] = [
            'type'          => 'resource',
            'name'          => 'filename',
            'resource_path' => htmlspecialchars($file_data['filename'], ENT_COMPAT, ABC::env('APP_CHARSET')),
            'rl_type'       => 'download',
        ]);

        $rl = new AResource('download');
        $rl_dir = $rl->getTypeDir();
        $resource_id = $rl->getIdFromHexPath(str_replace($rl_dir, '', $file_data['filename']));
        if ($resource_id) {
            $this->data['preview']['href'] = $this->html->getSecureURL(
                                                                    'common/resource_library/get_resource_preview',
                                                                    '&resource_id='.$resource_id,
                                                                    true
            );
            $this->data['preview']['path'] = 'resources/'.$file_data['filename'];
        }

        $this->data['form']['fields']['general']['status'] = $form->getFieldHtml([
            'type'    => 'checkbox',
            'name'    => 'status',
            'value'   => 1,
            'checked' => $file_data['status'] ? true : false,
            'style'   => 'btn_switch',
        ]);

        //check is download already shared
        if ($download_id) {
            $this->data['map_list'] = [];
            $file_data['map_list'] = (array)$this->model_catalog_download->getDownloadMapList($download_id);
            foreach ($file_data['map_list'] as $map_id => $map_name) {
                if ($map_id == $product_id) {
                    continue;
                }
                $this->data['map_list'][] = [
                    'href' => $this->html->getSecureURL(
                                                    'catalog/product_files',
                                                    '&product_id='.$map_id.'&download_id='.$this->data['download_id'],
                                                    true
                    ),
                    'text' => $map_name,
                ];
            }
            if (!sizeof($this->data['map_list'])) {
                $this->data['already_shared'] = false;
            } else {
                $this->data['already_shared'] = true;
            }
        }
        $this->data['delete_unmap_href'] = $this->html->getSecureURL(
                'catalog/product_files',
                '&act='.($file_data['shared'] ? 'unmap' : 'delete').'&product_id='.$product_id.'&download_id='.$this->data['download_id'],
                true);

        if ($product_id) {
            $this->data['form']['fields']['general']['shared'] = $form->getFieldHtml([
                'type'    => 'checkbox',
                'name'    => 'shared',
                'value'   => 1,
                'checked' => $file_data['shared'] ? true : false,
                'style'   => 'btn_switch '.($this->data['already_shared'] ? 'disabled' : ''),
            ]);
        }

        if ($file_data['shared']) {
            $this->data['text_attention_shared'] = $this->language->get('attention_shared');
        }

        $this->data['form']['fields']['general']['download_id'] = $form->getFieldHtml([
            'type'  => 'hidden',
            'name'  => 'download_id',
            'value' => $this->data['download_id'],
        ]);
        $this->data['form']['fields']['general']['name'] = $form->getFieldHtml([
            'type'     => 'input',
            'name'     => 'name',
            'value'    => $file_data['name'],
            'required' => true,
            'attr'     => ' maxlength="64" ',
        ]);
        $this->data['form']['fields']['general']['mask'] = $form->getFieldHtml([
            'type'  => 'input',
            'name'  => 'mask',
            'value' => $file_data['mask'],
        ]);

        $this->data['form']['fields']['general']['activate'] = $form->getFieldHtml([
            'type'     => 'selectbox',
            'name'     => 'activate',
            'value'    => $file_data['activate'],
            'options'  => [
                ''             => $this->language->get('text_select'),
                'before_order' => $this->language->get('text_before_order'),
                'immediately'  => $this->language->get('text_immediately'),
                'order_status' => $this->language->get('text_on_order_status'),
                'manually'     => $this->language->get('text_manually'),
            ],
            'required' => true,
            'style'    => 'download_activate no-save',
        ]);

        $options = ['' => $this->language->get('text_select')];
        foreach ($order_statuses as $order_status) {
            $options[$order_status['order_status_id']] = $order_status['name'];
        }

        $this->data['form']['fields']['general']['activate'] .= $form->getFieldHtml([
            'type'     => 'selectbox',
            'name'     => 'activate_order_status_id',
            'value'    => $file_data['activate_order_status_id'],
            'options'  => $options,
            'required' => true,
            'style'    => ' no-save ',
        ]);

        $this->data['form']['fields']['general']['sort_order'] = $form->getFieldHtml([
            'type'  => 'input',
            'name'  => 'sort_order',
            'style' => 'small-field',
            'value' => $file_data['sort_order'],
        ]);
        $this->data['form']['fields']['general']['max_downloads'] = $form->getFieldHtml([
            'type'  => 'input',
            'name'  => 'max_downloads',
            'value' => $file_data['max_downloads'],
            'style' => 'small-field',
        ]);

        $this->data['form']['fields']['general']['expire_days'] = $form->getFieldHtml([
            'type'  => 'input',
            'name'  => 'expire_days',
            'style' => 'small-field',
            'value' => $file_data['expire_days'],
        ]);
    }

    /**
     * @param AForm $form
     *
     * @throws \abc\core\lib\AException
     * @throws \ReflectionException
     */
    private function _buildAttributesSubform($form)
    {

        $attributes = $this->model_catalog_download->getDownloadAttributes($this->data['download_id']);
        $elements = HtmlElementFactory::getAvailableElements();

        $html_multivalue_elements = HtmlElementFactory::getMultivalueElements();
        $html_elements_with_options = HtmlElementFactory::getElementsWithOptions();
        if (!$attributes) {
            $attr_mng = ABC::getObjectByAlias('AttributeManager',['download_attribute']);
            $attr_type_id = $attr_mng->getAttributeTypeID('download_attribute');
            $this->data['form']['fields']['attributes']['no_attr'] =
                sprintf($this->language->get('text_no_download_attributes_yet'),
                        $this->html->getSecureURL(
                                                'catalog/attribute/insert',
                                                '&attribute_type_id='.$attr_type_id)
                );
        } else {
            foreach ($attributes as $attribute) {
                $html_type = $elements[$attribute['element_type']]['type'];
                if (!$html_type || !$attribute['status']) {
                    continue;
                }
                $values = $value = [];
                //values that has been set
                if (in_array($attribute['element_type'], $html_elements_with_options)
                    && $attribute['element_type'] != 'R') {
                    if (is_array($attribute['selected_values'])) {
                        foreach ($attribute['selected_values'] as $val) {
                            $value[$val] = $val;
                        }
                    } else {
                        $value = $attribute['selected_values'];
                    }
                } else {
                    if (isset($attribute['selected_values'])) {
                        $value = $attribute['selected_values'];
                        if ($attribute['element_type'] == 'R' && is_array($value)) {
                            $value = current($value);
                        }
                    } else {
                        $value = $attribute['values'][0]['value'];
                    }
                }

                $checked = false;
                if ($attribute['element_type'] == 'C') {
                    if ($value) {
                        $checked = true; //if value of attribute presents
                    } else {
                        $value = 1;
                    }
                }

                if ($attribute['element_type'] == 'S') {
                    $values[''] = $this->language->get('text_select'); // give ability to select nothing for selectbox
                }

                // possible values
                foreach ($attribute['values'] as $val) {
                    $values[$val['attribute_value_id']] = $val['value'];
                }

                if (!in_array($attribute['element_type'], $html_multivalue_elements)) {
                    $option_name = 'attributes['.(int)$this->data['download_id'].']['.$attribute['attribute_id'].']';
                } else {
                    $option_name = 'attributes['.(int)$this->data['download_id'].']['.$attribute['attribute_id'].']['
                        .$attribute['attribute_value_id'].']';
                }

                $disabled = '';
                $required = $attribute['required'];

                $option_data = [
                    'type'     => $html_type,
                    'name'     => $option_name,
                    'value'    => $value,
                    'options'  => $values,
                    'required' => $required,
                    'attr'     => $disabled,
                    'style'    => 'large-field',
                ];

                if ($html_type == 'checkboxgroup') {
                    $option_data['scrollbox'] = true;
                }
                if ($html_type == 'checkbox') {
                    $option_data['checked'] = $checked;
                    $option_data['style'] .= ' btn_switch';
                }

                $this->data['entry_'.$attribute['attribute_id']] = $attribute['name'];
                $this->data['form']['fields']['attributes'][$attribute['attribute_id']] = $form->getFieldHtml($option_data);
            }
        }

    }

    /**
     * @param array $data
     *
     * @return bool
     * @throws \Exception
     */
    private function _validateDownloadForm($data = [])
    {
        $this->error = [];
        if (!$this->user->canModify('catalog/product_files')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        $this->loadLanguage('catalog/files');
        $this->loadModel('catalog/download');

        if (!empty($data['download_id']) && !$this->model_catalog_download->getDownload($data['download_id'])) {
            $this->error['download_id'] = $this->language->get('error_download_exists');
        }

        if (mb_strlen($data['name']) < 2 || mb_strlen($data['name']) > 40) {
            $this->error['name'] = $this->language->get('error_download_name');
        }

        if (!in_array($data['activate'], ['before_order', 'immediately', 'order_status', 'manually'])) {
            $this->error['activate'] = $this->language->get('error_activate');
        } else {

            if ($data['activate'] == 'order_status' && !(int)$data['activate_order_status_id']) {
                $this->error['order_status'] = $this->language->get('error_order_status');
            }
        }
        /**
         * @var AttributeManagerInterface $attr_mngr
         */
        $attr_mngr = ABC::getObjectByAlias('AttributeManager',['download_attribute']);
        $attr_errors = $attr_mngr->validateAttributeData($data['attributes'][$data['download_id']]);
        if ($attr_errors) {
            $this->error['attributes'] = $attr_errors;
        }

        $this->extensions->hk_ValidateData($this);

        return $this->error ? false : true;
    }

    public function downloads()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $downloads = [];
        $this->loadModel('catalog/download');
        if ($this->request->post['id']) {
            $post_ids = (array)$this->request->post['id'];
            $ids = [];
            foreach ($post_ids as $id) {
                $ids[] = (int)$id;
            }
            $downloads = $this->model_catalog_download->getDownloads(
                [
                    'subsql_filter' => ' shared = 1 AND d.download_id IN ('.implode(',', $ids).')',
                ]
            );
        }

        $download_data = [];
        foreach ($downloads as $download) {
            $download_data[] = [
                'id'         => $download['download_id'],
                'name'       => $download['name'],
                'sort_order' => (int)$download['sort_order'],
            ];
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->load->library('json');
        $this->response->addJSONHeader();
        $this->response->setOutput(AJson::encode($download_data));
    }

    /**
     * Form to edit product from order
     */
    public function orderProductForm()
    {
        $opt_stock_message = null;

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadModel('catalog/product');

        $this->loadLanguage('catalog/product');
        $this->loadLanguage('sale/order');
        $this->load->library('json');

        $elements_with_options = HtmlElementFactory::getElementsWithOptions();
        $order_product_id = (int)$this->request->get['order_product_id'];
        $editable_price = (int)$this->request->get['editable_price'];
        $order_id = (int)$this->request->get['order_id'];
        $order_info = Order::getOrderArray($order_id);

        $tax = new ATax($this->registry);
        $tax->setZone($order_info['country_id'], $order_info['zone_id']);

        $product_id = (int)$this->request->get['product_id'];
        $product_info = $this->model_catalog_product->getProduct($product_id);
        $preset_values = [];

        if ($order_product_id) {

            //if unknown product_id but order_product_id we know
            /**
             * @var OrderProduct $order_product_info
             */
            $order_product_info = OrderProduct::where(['order_id'=>$order_id, 'order_product_id' => $order_product_id ] )->first();
            //$this->model_sale_order->getOrderProducts($order_id, $order_product_id);

            $preset_values['price'] = $this->currency->format(
                                            $order_product_info->price,
                                            $order_info['currency'], $order_info['value'],
                                            false
            );
            $preset_values['total'] = $this->currency->format((
                                            $order_product_info->price * $order_product_info->quantity),
                                            $order_info['currency'],
                                            $order_info['value'],
                                            false
            );
            $preset_values['quantity'] = $order_product_info->quantity;

            if (!$product_id) {
                $product_id = $order_product_info->product_id;
            }

            $order_product_options = OrderProduct::getOrderProductOptions($order_id, $order_product_id);
            foreach ($order_product_options as $v) {
                if ($v['element_type'] == 'R') {
                    $preset_values[(int)$v['product_option_id']] = (int)$v['product_option_value_id'];
                } elseif (in_array($v['element_type'], $elements_with_options)) {
                    $preset_values[(int)$v['product_option_id']][(int)$v['product_option_value_id']] = (int)$v['product_option_value_id'];
                } else {
                    $preset_values[(int)$v['product_option_id']] = (int)$v['value'];
                }
            }

            $this->data['text_title'] = $this->language->get('text_edit_order_product');
            $form_action = $this->html->getSecureURL(
                                                'sale/order/update',
                                                '&order_id='.$order_id.'&order_product_id='.$order_product_id
            );

        } elseif($order_id) {
            $this->data['text_title'] = sprintf($this->language->get('text_add_product_to_order'), $order_id);
            $preset_values['quantity'] = $product_info['minimum'] ? $product_info['minimum'] : 1;
            $preset_values['price'] = $this->currency->format(
                                                $product_info['price'],
                                                $order_info['currency'],
                                                $order_info['value'],
                                                false
            );
            $preset_values['total'] = $this->currency->format(
                                                    ($product_info['price'] * $preset_values['quantity']),
                                                    $order_info['currency'],
                                                    $order_info['value'],
                                                    false
            );

            $form_action = $this->html->getSecureURL(
                                                'sale/order/update',
                                                '&order_id='.$order_id.'&product_id='.$product_id
            );
        }
        //when trying to add new product to new order
        else{
            $this->data['text_title'] = sprintf($this->language->get('text_add_product_to_order'), $order_id);
            $preset_values['quantity'] = $product_info['minimum'] ? $product_info['minimum'] : 1;

            if($this->request->get['currency']) {
                $currency = new ACurrency($this->registry);
                $currency->set($this->request->get['currency']);
            }else{
                $currency = $this->currency;
            }

            $preset_values['price'] = $currency->format(
                                                $product_info['price'],
                                                $order_info['currency'],
                                                '',
                                                false
            );
            $preset_values['total'] = $currency->format(
                                                ($product_info['price'] * $preset_values['quantity']),
                                                $order_info['currency'],
                                                '',
                                                false
            );

            $form_action = $this->html->getSecureURL(
                                                $this->request->get['callback_rt'],
                                                '&product_id='.$product_id
            );
        }

        $this->data['product_href'] = $this->html->getSecureURL('catalog/product/update', '&product_id='.$product_id);

        $form = new AForm('HT');
        $form->setForm(
            [
                'form_name' => 'orderProductFrm',
            ]
        );

        $this->data['form']['id'] = 'orderProductFrm';
        $this->data['form']['form_open'] = $form->getFieldHtml([
            'type'   => 'form',
            'name'   => 'orderProductFrm',
            'attr'   => 'data-confirm-exit="true" class="aform form-horizontal"',
            'action' => $form_action,
        ]);

        $this->data['text_title'] .= ' - '.$product_info['name'];

        // Prepare options and values for display
        $product_options = Product::getProductOptionsWithValues($product_id);
        $options = [];
        foreach ($product_options as $option) {
            if (in_array($option['element_type'], ['U'])) {
                continue;
            } //skip files for now. TODO: add edit file-option in the future
            $values = $prices = [];
            $price = $preset_value = $default_value = '';
            foreach ($option['values'] as $option_value) {
                //default value
                $default_value = $option_value['default']&& !$order_product_id
                                ? $option_value['product_option_value_id']
                                : $default_value;
                //early saved value
                $preset_value = $preset_values[$option['product_option_id']];

                //when adds new product in the order
                if (!$order_product_id) {
                    if ($option_value['default'] == 1) {
                        $preset_value = $option_value['product_option_value_id'];
                    } elseif (!in_array($option['element_type'], $elements_with_options)) {
                        $preset_value = $option_value['description']['name'];
                    }
                }

                //Apply option price modifier
                if ($option_value['prefix'] == '%') {
                    $price = $tax->calculate(
                        ($product_info['price'] * $option_value['price'] / 100),
                        $product_info['tax_class_id'],
                        (bool)$this->config->get('config_tax'));
                } else {
                    $price = $tax->calculate(
                        $option_value['price'],
                        $product_info['tax_class_id'],
                        (bool)$this->config->get('config_tax'));
                }

                if ($price != 0) {
                    $price = $this->currency->format($price);
                } else {
                    $price = '';
                }

                //Check stock and status
                $opt_stock_message = '';
                if ($option_value['subtract']) {
                    if ($option_value['quantity'] <= 0) {
                        //show out of stock message
                        $opt_stock_message = ' ('.$this->language->get('text_product_out_of_stock').')';
                    } else {
                        if ($this->config->get('config_stock_display')) {
                            $opt_stock_message =
                                ' ('.$option_value['quantity']." ".$this->language->get('text_product_in_stock').')';
                        }
                    }
                }
                $values[$option_value['product_option_value_id']] =
                    $option_value['description']['name'].' '.$price.' '.$opt_stock_message;

            }

            //if not values are build, nothing to show
            if ($values) {

                //add price to option name if it is not element with options
                if (!in_array($option['element_type'], $elements_with_options)) {
                    $option['description']['name'] .= ' <small>'.$price.'</small>';
                    if ($opt_stock_message) {
                        $option['description']['name'] .= '<br />'.$opt_stock_message;
                    }
                }

                //set default selection is nothing selected
                if (!H::has_value($preset_value) && $option['element_type'] != 'C') {
                    if (H::has_value($default_value)) {
                        $preset_value = $default_value;
                    }
                }

                //show hidden option for admin
                if ($option['html_type'] == 'hidden') {
                    $option['html_type'] = 'input';
                }

                $value = $preset_value;
                //for checkbox with empty value

                if ($value == '' && $option['element_type'] == 'C') {
                    $value = $default_value;
                    $value = $value == '' ? 1 : $value;
                }

                $option_data = [
                    'type'           => $option['html_type'],
                    'name'           => !in_array($option['element_type'], HtmlElementFactory::getMultivalueElements())
                                        ? 'option['.$option['product_option_id'].']'
                                        : 'option['.$option['product_option_id'].'][]',
                    'value'          => $value,
                    'options'        => $values,
                    'placeholder'    => $option['option_placeholder'],
                    'regexp_pattern' => $option['regexp_pattern'],
                    'error_text'     => $option['error_text'],
                    'attr'           => ' data-option-id ="'.$option['product_option_id'].'"',
                ];
                if ($option['element_type'] == 'C') {
                    // note: 0 and 1 must be string to prevent collision with 'yes'. (in php 'yes'==1) ;-)
                    $option_data['label_text'] = !in_array($value, ['0', '1']) ? $value : '';
                    $option_data['checked'] = $preset_value ? true : false;
                }

                $options[] = [
                    'product_option_id'  => $option['product_option_id'],
                    'name'       => $option['description']['name'],
                    'html'       => $form->getFieldHtml($option_data),
                ];
            }
        }
        $this->data['options'] = $options;

        // main product image
        $resource = new AResource('image');
        $thumbnail = $resource->getMainThumb('products',
            $product_id,
            $this->config->get('config_image_product_width'),
            $this->config->get('config_image_product_height'));
        $this->data['image'] = $thumbnail;

        $this->data['form']['submit'] = $form->getFieldHtml([
            'type' => 'button',
            'name' => 'submit',
            'text' => $order_product_id ? $this->language->get('button_save') : $this->language->get('button_add'),
        ]);
        $this->data['form']['cancel'] = $form->getFieldHtml([
            'type' => 'button',
            'name' => 'cancel',
            'text' => $this->language->get('button_cancel'),
        ]);
        $this->data['form']['fields']['price'] = $form->getFieldHtml([
            'type'  => 'input',
            'name'  => 'price',
            'value' => $preset_values['price'],
            'attr'  => ($editable_price ? '' : 'readonly')
        ]);

        if (!$options && $product_info['subtract']) {
            if ($product_info['quantity']) {
                $this->data['column_quantity'] = $this->language->get('column_quantity')
                                                .' ('.$this->language->get('text_product_in_stock')
                                                .': '.$product_info['quantity'].')';
            } else {
                $this->data['column_quantity'] = $this->language->get('column_quantity')
                                                .' ('.$this->language->get('text_product_out_of_stock').')';
            }
        }

        $this->data['form']['fields']['quantity'] = $form->getFieldHtml([
            'type'  => 'input',
            'name'  => 'quantity',
            'value' => $preset_values['quantity'],
            'attr'  => ' size="4"',
        ]);

        $this->data['form']['fields']['total'] = $form->getFieldHtml([
            'type'  => 'input',
            'name'  => 'total',
            'value' => $preset_values['total'],
            'attr'  => 'readonly',
        ]);

        $this->data['form']['fields']['product_id'] = $form->getFieldHtml([
            'type'  => 'hidden',
            'name'  => 'product_id',
            'value' => $product_id,
        ]);

        $this->data['form']['fields']['order_product_id'] = $form->getFieldHtml([
            'type'  => 'hidden',
            'name'  => 'order_product_id',
            'value' => (int)$order_product_id,
        ]);

        //url to storefront response controller. Note: if admin under ssl - use https for url and otherwise
        $order_store_id = $order_info['store_id'];
        $store_info = Setting::getStoreSettings($order_store_id);
        if (ABC::env('HTTPS') && $store_info->config_ssl_url) {
            $total_calc_url = $store_info->config_ssl_url.'index.php?rt=r/product/product/calculateTotal';
        } elseif (ABC::env('HTTPS') && !$store_info->config_ssl_url) {
            $total_calc_url = str_replace(
                                        'http://',
                                        'https://',
                                        $store_info->config_url
                                        )
                                        .'index.php?rt=r/product/product/calculateTotal';
        } else {
            $total_calc_url = $store_info->config_url.'index.php?rt=r/product/product/calculateTotal';
        }

        $this->data['total_calc_url'] = $total_calc_url;

        $this->data['currency'] = $this->currency->getCurrency();
        $this->data['decimal_point'] = $this->language->get('decimal_point');
        $this->data['editable_price'] = $editable_price;
        $this->data['modal_mode'] = $this->request->get['mode'] == 'submit' ?: 'json';


        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->view->batchAssign($this->data);
        $this->processTemplate('responses/product/product_form.tpl');
    }

}
