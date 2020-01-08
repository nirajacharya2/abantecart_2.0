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

use abc\core\ABC;
use abc\core\engine\AController;
use abc\core\engine\Registry;
use abc\core\lib\AJson;
use abc\core\lib\FormBuilder;
use Illuminate\Validation\ValidationException;

class ControllerResponsesCatalogProductForm extends AController
{
    public $error = [];
    public $data = [];

    /**
     * @var $productInstance \abc\models\catalog\Product
     */
    private $productInstance;

    public function __construct(Registry $registry, $instance_id, $controller, $parent_controller = '')
    {
        $this->productInstance = ABC::getModelObjectByAlias('Product');
        parent::__construct($registry, $instance_id, $controller, $parent_controller);
    }

    public function main()
    {
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('catalog/product');
        $field = $this->request->post['field'];
        if ($field == 'product_type_id') {
            $this->getForm();
        }

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
                $formData['fields_preset']['fields'][$productFieldName]['value'] = $productField['value'];

                if (!is_numeric($productField['value']) && !is_array($productField['value'])) {
                    $formData['fields_preset']['fields'][$productFieldName]['value'] = htmlspecialchars_decode($productField['value']);
                }
            }
        }

        $form = new FormBuilder(ABC::getFullClassName('Product'), $product_type_id, $formData);
        $this->data['form_fields'] = $form->getForm()->getFormFields();


        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->load->library('json');
        $this->response->addJSONHeader();
        $this->response->setOutput(json_encode($this->data['form_fields']));

    }

    public function saveForm(array $fields)
    {
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->data['result'] = [];

        $fields['product_description'] = [
            'name'             => $fields['name'],
            'blurb'            => $fields['blurb'],
            'description'      => $fields['description'],
            'meta_keywords'    => $fields['meta_keywords'],
            'meta_description' => $fields['meta_description'],
            'language_id'      => $this->language->getContentLanguageID(),
        ];
        if (is_array($fields['categories']) && !empty($fields['categories'])) {
            $fields['product_category'] = $fields['categories'];
            unset($fields['categories']);
        }


        if (!isset($fields['product_store']) || empty($fields['product_store']) || $fields['product_store']==0) {
            $fields['product_stores'] = [0];
        }

        if ($this->validate($fields)) {

            try {
                $productId = $fields['product_id'];
                if ($productId) {
                    $this->productInstance->updateProduct($fields['product_id'], $fields);
                } else {
                    $product = $this->productInstance->createProduct($fields);
                    $productId = $product->product_id;
                }
                if ($productId) {
                    $this->data['result']['success_message'] = $this->language->get('text_saved');
                }

            } catch (ValidationException $e) {
                $this->data['result']['errors'] = $e->errors();
            } catch (\Exception $e) {
                $this->data['result']['error'] = $e->getMessage();
            }

        } else {
            $this->data['result']['error'] = $this->error['csrf'];
        }

        $this->data['result']['csrf'] = FormBuilder::getCsrfToken();

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->load->library('json');
        $this->response->addJSONHeader();
        $this->response->setOutput(AJson::encode($this->data['result']));
    }

    private function validate($data)
    {
        if (!$this->csrftoken->isTokenValid($data['csrfinstance'], $data['csrftoken'])) {
            $this->error['csrf'] = $this->language->get('text_system_error');
        }

        if (!empty($this->error)) {
            return false;
        }
        return true;
    }
}

