<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2021 Belavier Commerce LLC
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
use abc\core\lib\FormBuilder;
use abc\models\catalog\Product;
use Laracasts\Utilities\JavaScript\Transformers\Transformer;

class ControllerPagesCatalogProductForm extends AController
{
    public $error = [];
    public $data = [];

    public function main()
    {
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('catalog/product');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->view->assign('error_warning', $this->session->data['warning']);
        if (isset($this->session->data['warning'])) {
            unset($this->session->data['warning']);
        }
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
            'href'      => $this->html->getSecureURL('catalog/product'),
            'text'      => $this->language->get('heading_title'),
            'separator' => ' :: ',
            'current'   => true,
        ]);

        $product_type_id = (int)$this->request->get['product_type_id'];

        $formData = [
            'url'           => $this->html->getSecureURL('r/catalog/product_form'),
            'back_url'      => $this->html->getSecureURL('catalog/product'),
            'form_name'     => 'product_form',
            'title'         => 'Create New product',
            'fields_preset' => [
                'default' => [
                    "v_flex_props" => [
                        "xs12" => true,
                    ],
                ],
                'fields'  => [
                    'product_type_id' => [
                        'value' => $product_type_id,
                        'ajax_params'  => [
                            'relatedTo' => 'product_type_id',
                            'ajax_url'  => $this->html->getSecureURL('r/catalog/product_form'),
                        ],
                        "v_flex_props" => [
                            "xs12" => true,
                        ],
                    ],
                    'date_available'  => [
                        'value' => date('Y-m-d')
                    ],
                    'tax_class_id'  => [
                        'value' => 0
                    ],
                    'product_stores' => [
                        'value' => 0
                    ]
                ],
            ],
        ];

        $form = new FormBuilder(Product::class, $product_type_id, $formData);
        $this->data['form'] = $form->getForm()->toArray();

        $transformer = new Transformer($this->document, 'abc');
        $transformer->put(['form' => $this->data['form']]);

        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/catalog/product_vue_form.tpl');

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }
}
