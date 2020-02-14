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
use abc\core\engine\HtmlElementFactory;
use abc\core\lib\contracts\AttributeManagerInterface;
use abc\models\catalog\Product;
use abc\models\catalog\ProductOption;
use Exception;
use H;

class ControllerPagesCatalogProductOptions extends AController
{
    public $error = [];
    /**
     * @var AttributeManagerInterface
     */
    protected $attribute_manager;
    public $data = ['allowed_option_types' => ['I', 'T', 'S', 'M', 'R', 'C', 'G', 'H', 'U', 'B']];

    public function main()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('catalog/product');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->loadModel('catalog/product');
        $this->attribute_manager = ABC::getObjectByAlias('AttributeManager');
        $language_id = $this->language->getContentLanguageID();

        if ($this->request->is_POST() && $this->validateForm()) {

            $post = $this->request->post;
            if (!(int)$post['attribute_id']) {
                unset($post['attribute_id']);
            }
            $post['product_id'] = $this->data['product_id'] = $this->request->get['product_id'];
            $this->db->beginTransaction();
            try {
                $this->data['product_option_id'] = ProductOption::addProductOption($post);

                $this->extensions->hk_ProcessData($this);
                $this->session->data['success'] = $this->language->get('text_success');
                $this->db->commit();
                abc_redirect(
                    $this->html->getSecureURL(
                        'catalog/product_options',
                        '&product_id='.$this->data['product_id']
                        .'&product_option_id='.$this->data['product_option_id']
                    )
                );
            } catch (Exception $e) {
                $this->db->rollback();
                $this->log->error(__CLASS__.': '.$e->getMessage());
                $this->session->data['warning'] = H::getAppErrorText();
            }
        }

        $product = Product::with('description', 'options.description')->find($this->request->get['product_id']);
        if (!$product) {
            $this->session->data['warning'] = $this->language->get('error_product_not_found');
            abc_redirect($this->html->getSecureURL('catalog/product'));
        }

        $this->view->assign('error_warning', $this->session->data['warning']);
        if (isset($this->session->data['warning'])) {
            unset($this->session->data['warning']);
        }

        $this->data['attributes'] = [
            'new' => $this->language->get('text_add_new_option'),
        ];

        $results = $this->attribute_manager->getAttributes(
            [
                'search' =>
                    "ga.attribute_type_id = '".$this->attribute_manager->getAttributeTypeID('product_option')."'"
                        ." AND ga.status = 1 AND ga.attribute_parent_id IS NULL ",
                'sort'   => 'sort_order',
                'order'  => 'ASC',
                'limit'  => 1000 // !we can not have unlimited, so set 1000 for now
            ],
            $language_id
        );
        foreach ($results as $type) {
            $this->data['attributes'][$type['attribute_id']] = $type['name'];
        }

        $this->data['product_description'] = $product->description->toArray();
        $this->data['product_options'] = $product->options->toArray();
        $this->data['language_id'] = $language_id;
        $this->data['url']['load_option'] = $this->html->getSecureURL(
            'product/product/load_option',
            '&product_id='.$this->request->get['product_id']
        );
        $this->data['url']['update_option'] = $this->html->getSecureURL(
            'product/product/update_option',
            '&product_id='.$this->request->get['product_id']
        );
        $this->data['url']['get_options_list'] = $this->html->getSecureURL(
            'product/product/get_options_list',
            '&product_id='.$this->request->get['product_id']
        );

        $this->view->assign('error', $this->error);
        $this->view->assign('success', $this->session->data['success']);
        if (isset($this->session->data['success'])) {
            unset($this->session->data['success']);
        }

        $this->document->initBreadcrumb([
            'href' => $this->html->getSecureURL('index/home'),
            'text' => $this->language->get('text_home'),
        ]);
        $this->document->addBreadcrumb([
            'href' => $this->html->getSecureURL('catalog/product'),
            'text' => $this->language->get('heading_title'),
        ]);
        $this->document->addBreadcrumb([
            'href' => $this->html->getSecureURL(
                'catalog/product/update',
                '&product_id='.$this->request->get['product_id']
            ),
            'text' => $this->language->get('text_edit')
                .'&nbsp;'
                .$this->language->get('text_product')
                .' - '
                .$this->data['product_description']['name'],
        ]);
        $this->document->addBreadcrumb([
            'href'    => $this->html->getSecureURL(
                'catalog/product_options',
                '&product_id='.$this->request->get['product_id']),
            'text'    => $this->language->get('tab_option'),
            'current' => true,
        ]);

        $this->data['active'] = 'options';
        //load tabs controller
        $tabs_obj = $this->dispatch('pages/catalog/product_tabs', [$this->data]);
        $this->data['product_tabs'] = $tabs_obj->dispatchGetOutput();
        unset($tabs_obj);

        $results = HtmlElementFactory::getAvailableElements();
        $element_types = ['' => $this->language->get('text_select')];
        foreach ($results as $key => $type) {
            // allowed field types
            if (in_array($key, $this->data['allowed_option_types'])) {
                $element_types[$key] = $type['type'];
            }
        }

        $this->data['button_add_option'] = $this->html->buildButton(
            [
                'text'  => $this->language->get('button_add_option'),
                'style' => 'button1',
            ]
        );

        $this->data['button_add_option_value'] = $this->html->buildButton(
            [
                'text'  => $this->language->get('button_add_option_value'),
                'style' => 'button1',
            ]
        );

        $this->data['button_remove'] = $this->html->buildButton(
            [
                'text'  => $this->language->get('button_remove'),
                'style' => 'button1',
            ]
        );

        $this->data['button_reset'] = $this->html->buildButton(
            [
                'text'  => $this->language->get('button_reset'),
                'style' => 'button2',
            ]
        );

        $this->data['action'] = $this->html->getSecureURL(
            'catalog/product_options',
            '&product_id='.$this->request->get['product_id']
        );
        $this->data['form_title'] = $this->language->get('text_edit').'&nbsp;'.$this->language->get('text_product');
        $this->data['update'] = '';
        $form = new AForm('HT');

        $options_list = $product->options->pluck('description.name', 'product_option_id')->toArray();

        $product_option_id = $this->request->get['product_option_id']
            ? $this->request->get['product_option_id']
            : $this->data['product_option_id'];

        $this->data['options'] = $form->getFieldHtml([
            'type'    => 'selectbox',
            'name'    => 'option',
            'value'   => $product_option_id,
            'options' => $options_list,
        ]);

        $form->setForm([
            'form_name' => 'product_form',
            'update'    => $this->data['update'],
        ]);

        $this->data['form']['id'] = 'product_form';
        $this->data['form']['form_open'] = $form->getFieldHtml([
            'type'   => 'form',
            'name'   => 'product_form',
            'action' => $this->data['action'],
            'attr'   => 'data-confirm-exit="true"  class="form-horizontal"',
        ]);
        $this->data['form']['submit'] = $form->getFieldHtml([
            'type'  => 'button',
            'name'  => 'submit',
            'text'  => $this->language->get('button_add'),
            'style' => 'button1',
        ]);
        $this->data['form']['cancel'] = $form->getFieldHtml([
            'type'  => 'button',
            'name'  => 'cancel',
            'text'  => $this->language->get('button_cancel'),
            'style' => 'button2',
        ]);

        $form->setForm([
            'form_name' => 'new_option_form',
            'update'    => '',
        ]);
        $this->data['attributes'] = $form->getFieldHtml([
            'type'    => 'selectbox',
            'name'    => 'attribute_id',
            'options' => $this->data['attributes'],
            'style'   => 'chosen',
        ]);
        $this->data['option_name'] = $form->getFieldHtml([
            'type'     => 'input',
            'name'     => 'option_name',
            'required' => true,
        ]);
        $this->data['status'] = $form->getFieldHtml([
            'type'  => 'checkbox',
            'name'  => 'status',
            'value' => 1,
            'style' => 'btn_switch',
        ]);
        $this->data['sort_order'] = $form->getFieldHtml([
            'type'  => 'input',
            'name'  => 'sort_order',
            'style' => 'small-field',
        ]);
        $this->data['required'] = $form->getFieldHtml([
            'type'  => 'checkbox',
            'name'  => 'required',
            'style' => 'btn_switch',
        ]);
        $this->data['element_type'] = $form->getFieldHtml([
            'type'     => 'selectbox',
            'name'     => 'element_type',
            'required' => true,
            'options'  => $element_types,
        ]);

        $this->addChild('pages/catalog/product_summary', 'summary_form', 'pages/catalog/product_summary.tpl');
        $object_title = $this->language->get('text_product').' '.$this->language->get('text_option_value');
        $params = '&object_name=product_option_value&object_title='.$object_title;
        foreach (
            [
                'rl_resource_library' => 'common/resource_library',
                'rl_resources'        => 'common/resource_library/resources',
                'rl_resource_single'  => 'common/resource_library/get_resource_details',
                'rl_delete'           => 'common/resource_library/delete',
                'rl_unmap'            => 'common/resource_library/unmap',
                'rl_map'              => 'common/resource_library/map',
                'rl_download'         => 'common/resource_library/get_resource_preview',
                'rl_upload'           => 'common/resource_library/upload',
            ]
            as $key => $rt
        ) {
            $this->data[$key] =
                $this->html->getSecureURL($rt, (!in_array($key, ['rl_download', 'rl_delete']) ? $params : ''));
        }

        $resources_scripts = $this->dispatch(
            'responses/common/resource_library/get_resources_scripts',
            [
                'object_name' => 'product_option_value',
                'object_id'   => '',
                'types'       => ['image'],
                //sign loading thumbs on page load.
                // disable it for hidden attribute values info
                'onload'      => false,
            ]
        );
        if ($this->config->get('config_embed_status')) {
            $this->data['embed_url'] = $this->html->getSecureURL(
                'common/do_embed/product',
                '&product_id='.$this->request->get['product_id']
            );
        }
        $this->view->assign('resources_scripts', $resources_scripts->dispatchGetOutput());
        $this->view->assign('help_url', $this->gen_help_url('product_options'));
        $this->view->assign('form_language_switch', $this->html->getContentLanguageSwitcher());
        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/catalog/product_options.tpl');

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    protected function validateForm()
    {
        if (!$this->user->canModify('catalog/product_options')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        if (ProductOption::isProductGroupOption(
            $this->request->get['product_id'],
            $this->request->post['attribute_id'])
        ) {
            $this->error['warning'] = $this->language->get('error_option_in_group');
        }

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }
}