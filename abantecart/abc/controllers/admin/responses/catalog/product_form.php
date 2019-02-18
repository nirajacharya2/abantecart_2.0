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
use abc\core\lib\AJson;
use abc\core\lib\FormBuilder;
use abc\models\catalog\Product;

class ControllerResponsesCatalogProductForm extends AController
{
    public $error = [];
    public $data = [];

    public function main()
    {
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('catalog/product');
        $field = $this->request->post['field'];
        if ($field == 'product_type_id') {
            $this->getForm();
        }

        \H::df($this->request->post['fields']);

        $fields = $this->request->post['fields'];
        $saveForm = $this->request->post['saveForm'];
        if (is_array($fields) && (bool)$saveForm === true) {
            $this->saveForm($fields);
        }

        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function getForm()
    {
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $product_type_id = (int)$this->request->post['field_value'];

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
                        'ajax_params'  => [
                            'relatedTo' => 'product_type_id',
                            'ajax_url'  => $this->html->getSecureURL('r/catalog/product_form'),
                        ],
                        "v_flex_props" => [
                            "xs12" => true,
                        ],
                    ],
                ],
            ],
        ];

        $productFields = $this->request->post['fields'];

        if ($productFields && is_array($productFields)) {
            foreach ($productFields as $productFieldName => $productField) {
                if (empty($productField['value'])) {
                    continue;
                }
                if ((int)$productField['value'] > 0 && $productField['value'] !== '0') {
                    $formData['fields_preset']['fields'][$productFieldName]['value'] = (int)$productField['value'];
                } else {
                    $formData['fields_preset']['fields'][$productFieldName]['value'] = $productField['value'];
                }
            }
        }

        $form = new FormBuilder(Product::class, $product_type_id, $formData);
        $this->data['form_fields'] = $form->getForm()->getFormFields();

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->load->library('json');
        $this->response->addJSONHeader();
        $this->response->setOutput(json_encode($this->data['form_fields']));

    }

    public function saveForm(array $fileds) {
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->data['result'] = [];

        foreach ($fileds as $key => $field) {

        }
        $this->data['result']['success_message'] = 'Form save complete!';

        $this->data['result']['errors'][] = 'Erro1';
        $this->data['result']['errors'][] = 'Erro2';

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->load->library('json');
        $this->response->addJSONHeader();
        $this->response->setOutput(AJson::encode($this->data['result']));
    }
}

