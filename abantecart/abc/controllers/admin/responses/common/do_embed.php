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
use abc\core\engine\AForm;
use abc\models\catalog\Category;
use abc\models\locale\Currency;
use H;

class ControllerResponsesCommonDoEmbed extends AController
{
    public $data = [];

    public function main()
    {
    }

    public function product()
    {
        if (!H::has_value($this->request->get['product_id'])) {
            return null;
        }
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $form = new AForm('ST');
        $form->setForm([
            'form_name' => 'getEmbedFrm',
        ]);
        $this->data['form']['form_open'] = $form->getFieldHtml([
            'type' => 'form',
            'name' => 'getEmbedFrm',
            'attr' => 'class="aform form-horizontal"',
        ]);

        $this->data['fields'][] = $form->getFieldHtml([
            'type'  => 'checkbox',
            'name'  => 'image',
            'value' => 1,
            'style' => 'btn_switch btn-group-xs',
        ]);
        $this->data['fields'][] = $form->getFieldHtml([
            'type'  => 'checkbox',
            'name'  => 'name',
            'value' => 1,
            'style' => 'btn_switch btn-group-xs',
        ]);
        $this->data['fields'][] = $form->getFieldHtml([
            'type'  => 'checkbox',
            'name'  => 'blurb',
            'value' => 1,
            'style' => 'btn_switch btn-group-xs',
        ]);
        $this->data['fields'][] = $form->getFieldHtml([
            'type'  => 'checkbox',
            'name'  => 'price',
            'value' => 1,
            'style' => 'btn_switch btn-group-xs',
        ]);
        $this->data['fields'][] = $form->getFieldHtml([
            'type'  => 'checkbox',
            'name'  => 'rating',
            'value' => 1,
            'style' => 'btn_switch btn-group-xs',
        ]);
        $this->data['fields'][] = $form->getFieldHtml([
            'type'  => 'checkbox',
            'name'  => 'quantity',
            'value' => 1,
            'style' => 'btn_switch btn-group-xs',
        ]);
        $this->data['fields'][] = $form->getFieldHtml([
            'type'  => 'checkbox',
            'name'  => 'addtocart',
            'value' => 1,
            'style' => 'btn_switch btn-group-xs',
        ]);

        $results = $this->language->getAvailableLanguages();
        $languages = $language_codes = [];
        foreach ($results as $v) {
            $languages[$v['code']] = $v['name'];
            $lng_code = $this->language->getLanguageCodeByLocale($v['locale']);
            $language_codes[$lng_code] = $v['name'];
        }
        $this->data['fields'][] = $form->getFieldHtml([
            'type'    => 'selectbox',
            'name'    => 'language',
            'value'   => $this->config->get('config_storefront_language'),
            'options' => $language_codes,
        ]);

        $this->load->model('localisation/currency');
        $results = Currency::all()->toArray();
        $currencies = [];
        foreach ($results as $v) {
            $currencies[$v['code']] = $v['title'];
        }
        $this->data['fields'][] = $form->getFieldHtml([
            'type'    => 'selectbox',
            'name'    => 'currency',
            'value'   => $this->config->get('config_currency'),
            'options' => $currencies,
        ]);

        $this->data['text_area'] = $form->getFieldHtml([
            'type'  => 'textarea',
            'name'  => 'code_area',
            'attr'  => 'rows="10"',
            'style' => 'ml_field',
        ]);

        $this->loadModel('catalog/product');
        $this->loadModel('setting/store');
        //if loaded not default store - hide store switcher
        $current_store_settings = $this->model_setting_store->getStore($this->config->get('config_store_id'));
        $remote_store_url = $current_store_settings['config_url'];
        $product_id = $this->request->get['product_id'];
        $this->data['product_id'] = $product_id;

        $product_stores = $this->model_catalog_product->getProductStoresInfo($product_id);

        if (sizeof($product_stores) == 1) {
            $remote_store_url = $product_stores[0]['store_url'];
        }
        $remote_store_url = $this->prepareUrl($remote_store_url);
        $this->data['sf_js_embed_url'] = $remote_store_url.ABC::env('INDEX_FILE').'?rt=r/embed/js';
        $this->data['sf_base_url'] = $remote_store_url;
        $this->data['help_url'] = $this->gen_help_url('embed');

        $template_name = $this->config->get('config_storefront_template');
        $this->data['sf_css_embed_url'] = $remote_store_url.'storefront/view/default/css/embed.css';
        //override css url for extension templates
        if ($template_name != 'default') {
            $css_file =
                ABC::env('DIR_ROOT').'/extensions/'.$template_name.'/storefront/view/'.$template_name.'/css/embed.css';
            if (is_file($css_file)) {
                $this->data['sf_css_embed_url'] =
                    $remote_store_url.'extensions/'.$template_name.'/storefront/view/'.$template_name.'/css/embed.css';
            }
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->loadlanguage('common/do_embed');
        $this->view->batchAssign($this->language->getASet('common/do_embed'));
        $this->view->batchAssign($this->data);
        $this->processTemplate('responses/embed/do_embed_product_modal.tpl');
    }

    public function categories()
    {

        //this var can be an array
        $category_id = (array)$this->request->get['category_id'];
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $form = new AForm('ST');
        $form->setForm([
            'form_name' => 'getEmbedFrm',
        ]);
        $this->data['form']['form_open'] = $form->getFieldHtml([
            'type' => 'form',
            'name' => 'getEmbedFrm',
            'attr' => 'class="aform form-horizontal"',
        ]);

        $this->data['fields'][] = $form->getFieldHtml([
            'type'  => 'checkbox',
            'name'  => 'image',
            'value' => 1,
            'style' => 'btn_switch btn-group-xs',
        ]);
        $this->data['fields'][] = $form->getFieldHtml([
            'type'  => 'checkbox',
            'name'  => 'name',
            'value' => 1,
            'style' => 'btn_switch btn-group-xs',
        ]);

        $this->data['fields'][] = $form->getFieldHtml([
            'type'  => 'checkbox',
            'name'  => 'products_count',
            'value' => 1,
            'style' => 'btn_switch btn-group-xs',
        ]);

        $results = $this->language->getAvailableLanguages();
        $languages = $language_codes = [];
        foreach ($results as $v) {
            $languages[$v['code']] = $v['name'];
            $lng_code = $this->language->getLanguageCodeByLocale($v['locale']);
            $language_codes[$lng_code] = $v['name'];
        }
        $this->data['fields'][] = $form->getFieldHtml([
            'type'    => 'selectbox',
            'name'    => 'language',
            'value'   => $this->config->get('config_storefront_language'),
            'options' => $language_codes,
        ]);

        $this->load->model('localisation/currency');
        $results = Currency::all()->toArray();
        $currencies = [];
        foreach ($results as $v) {
            $currencies[$v['code']] = $v['title'];
        }
        $this->data['fields'][] = $form->getFieldHtml([
            'type'    => 'selectbox',
            'name'    => 'currency',
            'value'   => $this->config->get('config_currency'),
            'options' => $currencies,
        ]);

        $this->data['text_area'] = $form->getFieldHtml([
            'type'  => 'textarea',
            'name'  => 'code_area',
            'attr'  => 'rows="10"',
            'style' => 'ml_field',
        ]);

        $this->loadModel('catalog/category');
        $this->loadModel('setting/store');
        //if loaded not default store - hide store switcher
        $current_store_settings = $this->model_setting_store->getStore($this->config->get('config_store_id'));
        $remote_store_url = $current_store_settings['config_url'];

        $subcategories = [];
        //if embed for only one category
        if (sizeof($category_id) == 1) {
            $cat_id = current($category_id);
            $category_info = (new Category())->getCategory($cat_id);
            $category_stores = (new Category())->getCategoryStoresInfo($cat_id);

            if (sizeof($category_stores) == 1) {
                $remote_store_url = $category_stores[0]['store_url'];
            }
            $subcategories = (new Category())->getCategories($cat_id);
            if ($category_info['parent_id'] == 0) {
                $options = (new Category())->getCategories(0);
            } else {
                $cat_desc = (new Category())->getCategoryDescriptions($cat_id);
                $options = [
                    0 =>
                        [
                            'category_id' => $cat_id,
                            'name'        => $cat_desc[$this->language->getContentLanguageID()]['name'],
                        ],
                ];
            }
        } else {
            if (!sizeof($category_id)) {
                $options = Category::getCategoriesData(['parent_id' => 0]);
                $category_id = [];
                foreach ($options as $c) {
                    $category_id[] = $c['category_id'];
                }
            } else {
                foreach ($category_id as &$c) {
                    $c = (int)$c;
                }
                unset($c);
                $subsql = ' c.category_id IN ('.implode(',', $category_id).') ';
                $options = Category::getCategoriesData(['subsql_filter' => $subsql]);
            }
        }

        if ($subcategories) {
            $options = array_merge($options, $subcategories);
        }
        $opt = [];
        foreach ($options as $cat) {
            $opt[$cat['category_id']] = $cat['name'];
        }

        $this->data['fields'][] = $form->getFieldHtml([
            'type'      => 'checkboxgroup',
            'name'      => 'category_id[]',
            'value'     => $category_id,
            'options'   => $opt,
            'scrollbox' => true,
            'style'     => 'medium-field',
        ]);

        $this->data['text_area'] = $form->getFieldHtml([
            'type'  => 'textarea',
            'name'  => 'code_area',
            'attr'  => 'rows="10"',
            'style' => 'ml_field',
        ]);

        $this->data['category_id'] = $this->request->get['category_id'];

        $remote_store_url = $this->prepareUrl($remote_store_url);
        $this->data['sf_js_embed_url'] = $remote_store_url.ABC::env('INDEX_FILE').'?rt=r/embed/js';
        $this->data['sf_base_url'] = $remote_store_url;
        $this->data['help_url'] = $this->gen_help_url('embed');

        $this->data['sf_css_embed_url'] =
            $remote_store_url.'storefront/view/'.$this->config->get('config_storefront_template').'/css/embed.css';

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->loadlanguage('common/do_embed');
        $this->view->batchAssign($this->language->getASet('common/do_embed'));
        $this->view->batchAssign($this->data);
        $this->processTemplate('responses/embed/do_embed_category_modal.tpl');
    }

    public function manufacturers()
    {
        //this var can be an array
        $manufacturer_id = (array)$this->request->get['manufacturer_id'];
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $form = new AForm('ST');
        $form->setForm([
            'form_name' => 'getEmbedFrm',
        ]);
        $this->data['form']['form_open'] = $form->getFieldHtml([
            'type' => 'form',
            'name' => 'getEmbedFrm',
            'attr' => 'class="aform form-horizontal col-sm-12"',
        ]);

        $this->data['fields'][] = $form->getFieldHtml([
            'type'  => 'checkbox',
            'name'  => 'image',
            'value' => 1,
            'style' => 'btn_switch btn-group-xs',
        ]);
        $this->data['fields'][] = $form->getFieldHtml([
            'type'  => 'checkbox',
            'name'  => 'name',
            'value' => 0,
            'style' => 'btn_switch btn-group-xs',
        ]);

        $this->data['fields'][] = $form->getFieldHtml([
            'type'  => 'checkbox',
            'name'  => 'products_count',
            'value' => 1,
            'style' => 'btn_switch btn-group-xs',
        ]);

        $results = $this->language->getAvailableLanguages();
        $languages = $language_codes = [];
        foreach ($results as $v) {
            $languages[$v['code']] = $v['name'];
            $lng_code = $this->language->getLanguageCodeByLocale($v['locale']);
            $language_codes[$lng_code] = $v['name'];
        }
        $this->data['fields'][] = $form->getFieldHtml([
            'type'    => 'selectbox',
            'name'    => 'language',
            'value'   => $this->config->get('config_storefront_language'),
            'options' => $language_codes,
        ]);

        $results = Currency::all()->toArray();
        $currencies = [];
        foreach ($results as $v) {
            $currencies[$v['code']] = $v['title'];
        }
        $this->data['fields'][] = $form->getFieldHtml([
            'type'    => 'selectbox',
            'name'    => 'currency',
            'value'   => $this->config->get('config_currency'),
            'options' => $currencies,
        ]);

        $this->loadModel('catalog/manufacturer');
        $this->loadModel('setting/store');
        //if loaded not default store - hide store switcher
        $current_store_settings = $this->model_setting_store->getStore($this->config->get('config_store_id'));
        $remote_store_url = $current_store_settings['config_url'];

        if (!sizeof($manufacturer_id)) {
            return null;
        } else {
            foreach ($manufacturer_id as &$c) {
                $c = (int)$c;
            }
            unset($c);
            $subsql = ' m.manufacturer_id IN ('.implode(',', $manufacturer_id).') ';
            $options = $this->model_catalog_manufacturer->getManufacturers(['subsql_filter' => $subsql]);
        }
        reset($manufacturer_id);

        $opt = [];
        foreach ($options as $m) {
            $opt[$m['manufacturer_id']] = $m['name'];
        }
        if (sizeof($manufacturer_id) > 1) {
            $this->data['fields'][] = $form->getFieldHtml([
                'type'      => 'checkboxgroup',
                'name'      => 'manufacturer_id[]',
                'value'     => $manufacturer_id,
                'options'   => $opt,
                'scrollbox' => true,
                'style'     => 'medium-field',
            ]);
        } else {

            $this->data['fields'][] = $form->getFieldHtml([
                'type'  => 'hidden',
                'name'  => 'manufacturer_id[]',
                'value' => current($manufacturer_id),
            ]);

            $manufacturer_stores =
                $this->model_catalog_manufacturer->getManufacturerStoresInfo(current($manufacturer_id));

            if (sizeof($manufacturer_stores) == 1) {
                $remote_store_url = $manufacturer_stores[0]['store_url'];
            }
        }

        $this->data['text_area'] = $form->getFieldHtml([
            'type'  => 'textarea',
            'name'  => 'code_area',
            'attr'  => 'rows="10"',
            'style' => 'ml_field',
        ]);

        $this->data['manufacturer_id'] = $this->request->get['manufacturer_id'];
        $remote_store_url = $this->prepareUrl($remote_store_url);
        $this->data['sf_js_embed_url'] = $remote_store_url.ABC::env('INDEX_FILE').'?rt=r/embed/js';
        $this->data['sf_base_url'] = $remote_store_url;
        $this->data['help_url'] = $this->gen_help_url('embed');

        $this->data['sf_css_embed_url'] =
            $remote_store_url.'storefront/view/'.$this->config->get('config_storefront_template').'/css/embed.css';

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->loadlanguage('common/do_embed');
        $this->view->batchAssign($this->language->getASet('common/do_embed'));
        $this->view->batchAssign($this->data);
        $this->processTemplate('responses/embed/do_embed_manufacturer_modal.tpl');
    }

    protected function prepareUrl($url)
    {
        return str_replace(['http://', 'https://'], '//', $url);
    }
}
