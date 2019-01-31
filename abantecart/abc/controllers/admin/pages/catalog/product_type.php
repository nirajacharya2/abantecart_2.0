<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2018 Belavier Commerce LLC
 *
 * This source file is subject to Open Software License (OSL 3.0)
 * License details is bundled with this package in the file LICENSE.txt.
 * It is also available at this URL:
 * <http://www.opensource.org/licenses/OSL-3.0>
 *
 * UPGRADE NOTE:
 * Do not edit or add to this file if you wish to upgrade AbanteCart to newer
 * versions in the future. If you wish to customize AbanteCart for your
 * needs please refer to http://www.abantecart.com for more information.
 */

namespace abc\controllers\admin;

use abc\core\engine\AController;
use abc\core\engine\AForm;
use abc\models\catalog\GlobalAttributesGroup;
use abc\models\catalog\ProductType;
use abc\models\catalog\ProductTypeDescription;

class ControllerPagesCatalogProductType extends AController
{
    private $error = array();
    public $data = array();

    public function main()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('catalog/product_type');

        $this->document->setTitle($this->language->get('heading_title'));
        $this->data['heading_title'] = $this->language->get('heading_title');

        $this->view->assign('error_warning', $this->error['warning']);
        $this->view->assign('success', $this->session->data['success']);
        if (isset($this->session->data['success'])) {
            unset($this->session->data['success']);
        }

        $this->document->initBreadcrumb([
            'href'      => $this->html->getSecureURL('index/home'),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);
        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('catalog/product_type'),
            'text'      => $this->language->get('heading_title'),
            'separator' => ' :: ',
            'current'   => true,
        ]);

        $grid_settings = [
            'table_id'       => 'attribute_groups_grid',
            'url'            => $this->html->getSecureURL('listing_grid/product_types'),
            'editurl'        => $this->html->getSecureURL('listing_grid/product_types/update'),
            'update_field'   => $this->html->getSecureURL('listing_grid/product_types/update_field'),
            'sortname'       => 'sort_order',
            'sortorder'      => 'desc',
            'columns_search' => false,
            'actions'        => [
                'edit'   => [
                    'text' => $this->language->get('text_edit'),
                    'href' => $this->html->getSecureURL('catalog/product_type/update', '&product_type_id=%ID%'),
                ],
                'save'   => [
                    'text' => $this->language->get('button_save'),
                ],
                'delete' => [
                    'text' => $this->language->get('button_delete'),
                ],
            ],
        ];

        $grid_settings['colNames'] = [
            $this->language->get('column_product_type_title'),
            $this->language->get('column_product_type_sort_order'),
            $this->language->get('column_product_type_status'),
        ];
        $grid_settings['colModel'] = [
            [
                'name'  => 'title',
                'index' => 'title',
                'align' => 'left',
            ],
            [
                'name'  => 'sort_order',
                'index' => 'sort_order',
                'align' => 'center',
            ],
            [
                'name'  => 'status',
                'index' => 'status',
                'align' => 'center',
            ],
        ];

        $grid = $this->dispatch('common/listing_grid', [$grid_settings]);
        $this->view->assign('listing_grid', $grid->dispatchGetOutput());

        $this->view->assign('insert', $this->html->getSecureURL('catalog/product_type/insert'));
        $this->view->assign('form_language_switch', $this->html->getContentLanguageSwitcher());
        $this->view->assign('help_url', $this->gen_help_url('product_type_listing'));

        $this->processTemplate('pages/catalog/product_type_list.tpl');

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

    }

    public function insert()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->document->setTitle($this->language->get('heading_title'));

        if ($this->request->is_POST() && $this->validateForm()) {
            $post = $this->request->post;
            $arProductType = [
                'status' => $post['status'],
                'sort_order' => $post['sort_order'],
            ];
            $product_type = new ProductType($arProductType);
            $product_type->save();

            $product_type_id = $product_type->product_type_id;

            $arDescription = [
                'title' => $post['title'],
                'language_id' => $this->language->getContentLanguageID(),
            ];

            $product_type->descriptions()->create($arDescription);

            $this->session->data['success'] = $this->language->get('text_success');
            abc_redirect(
                $this->html->getSecureURL(
                    'catalog/product_type/update',
                    '&product_type_id='.$product_type_id
                )
            );
        }
        $this->getForm();

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    protected function getForm()
    {
        $this->data = [];
        $this->data['error'] = $this->error;
        $this->data['cancel'] = $this->html->getSecureURL('catalog/product_type');

        $this->document->initBreadcrumb([
            'href'      => $this->html->getSecureURL('index/home'),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);
        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('catalog/product_type'),
            'text'      => $this->language->get('heading_title'),
            'separator' => ' :: ',
            'current'   => true,
        ]);
        $language_id = $this->language->getContentLanguageID();

        if (isset($this->request->get['product_type_id']) && $this->request->is_GET()) {
            $productTypeInst = new ProductType();
            $product_type_info = $productTypeInst->getProductType($this->request->get['product_type_id'], $language_id);
        }

        $fields = ['title', 'sort_order', 'status'];
        foreach ($fields as $f) {
            if (isset($this->request->post[$f])) {
                $this->data[$f] = $this->request->post[$f];
            } elseif (isset($product_type_info)) {
                $this->data[$f] = $product_type_info[$f];
            } else {
                $this->data[$f] = '';
            }
        }

        if (!isset($this->request->get['product_type_id'])) {
            $this->data['action'] = $this->html->getSecureURL('catalog/product_type/insert');
            $this->data['heading_title'] = $this->language->get('text_insert').$this->language->get('text_group');
            $this->data['update'] = '';
            $form = new AForm('ST');
        } else {
            $this->data['action'] = $this->html->getSecureURL(
                'catalog/product_type/update',
                '&product_type_id='.$this->request->get['product_type_id']
            );
            $this->data['heading_title'] = $this->language->get('text_edit').$this->language->get('text_group');
            $this->data['update'] = $this->html->getSecureURL(
                'listing_grid/product_types/update_field',
                '&id='.$this->request->get['product_type_id']
            );
            $form = new AForm('HS');
        }

        $this->document->addBreadcrumb([
            'href'      => $this->data['action'],
            'text'      => $this->data['heading_title'],
            'separator' => ' :: ',
        ]);

        $form->setForm([
            'form_name' => 'editFrm',
            'update'    => $this->data['update'],
        ]);

        $this->data['form']['id'] = 'editFrm';
        $this->data['form']['form_open'] = $form->getFieldHtml([
            'type'   => 'form',
            'name'   => 'editFrm',
            'attr'   => 'data-confirm-exit="true"',
            'action' => $this->data['action'],
        ]);
        $this->data['form']['submit'] = $form->getFieldHtml([
            'type'  => 'button',
            'name'  => 'submit',
            'text'  => $this->language->get('button_save'),
            'style' => 'button1',
        ]);
        $this->data['form']['cancel'] = $form->getFieldHtml([
            'type'  => 'button',
            'name'  => 'cancel',
            'text'  => $this->language->get('button_cancel'),
            'style' => 'button2',
        ]);

        $this->data['form']['fields']['status'] = $form->getFieldHtml([
            'type'  => 'checkbox',
            'name'  => 'status',
            'value' => $this->data['status'],
            'style' => 'btn_switch',
        ]);

        $this->data['form']['fields']['sort_order'] = $form->getFieldHtml([
            'type'  => 'input',
            'name'  => 'sort_order',
            'value' => $this->data['sort_order'],
            'style' => 'small-field',
        ]);

        $this->data['form']['fields']['title'] = $form->getFieldHtml([
            'type'     => 'input',
            'name'     => 'title',
            'value'    => $this->data['title'],
            'required' => true,
            'style'    => 'small-field',
        ]);

        $attributeGroups = GlobalAttributesGroup::with('description')->where('status', 1)->get()->toArray();
        $attribute_groups = [];
        foreach ($attributeGroups as $attributeGroup) {
            $attribute_groups[$attributeGroup['attribute_group_id']] = $attributeGroup['description']['name'];
        }
        unset($attributeGroups);

        $this->data['attribute_group'] = [];

        $this->data['form']['fields']['attribute_group'] = $form->getFieldHtml(
            [
                'type'    => 'checkboxgroup',
                'name'    => 'attribute_group[]',
                'value'   => $this->data['attribute_group'],
                'options' => $attribute_groups,
                'style'   => 'chosen',
            ]);

        $this->view->batchAssign($this->data);
        $this->view->assign('form_language_switch', $this->html->getContentLanguageSwitcher());
        $this->view->assign('language_id', $this->session->data['content_language_id']);
        $this->view->assign('help_url', $this->gen_help_url('product_type_edit'));
        $this->processTemplate('pages/catalog/product_type_form.tpl');
    }

    protected function validateForm()
    {
       // if (!$this->user->canModify('catalog/attribute_groups')) {
         //   $this->error['warning'] = $this->language->get('error_permission');
       // }

        //if (mb_strlen($this->request->post['name']) < 2 || mb_strlen($this->request->post['name']) > 32) {
          //  $this->error['name'] = $this->language->get('error_name');
       // }

        $this->extensions->hk_ValidateData($this);

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }

}