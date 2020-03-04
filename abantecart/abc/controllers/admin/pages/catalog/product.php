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
use abc\core\engine\AForm;
use abc\core\engine\Registry;
use abc\core\lib\FormBuilder;
use abc\models\catalog\Category;
use abc\models\catalog\Manufacturer;
use abc\models\catalog\ObjectType;
use abc\models\catalog\Product;
use abc\models\catalog\UrlAlias;
use abc\models\QueryBuilder;
use abc\models\system\Store;
use H;
use Laracasts\Utilities\JavaScript\PHPToJavaScriptTransformer;

/**
 * Class ControllerPagesCatalogProduct
 *
 * @package abc\controllers\admin
 */
class ControllerPagesCatalogProduct extends AController
{
    public $error = [];
    public $data = [];
    /**
     * @var $productInstance \abc\models\catalog\Product
     */
    private $productInstance;

    public function __construct(Registry $registry, $instance_id, $controller, $parent_controller = '')
    {
        //temporary disable new product form
        $this->data['oldForm'] = true;
        $this->productInstance = ABC::getModelObjectByAlias('Product');
        parent::__construct($registry, $instance_id, $controller, $parent_controller);
    }

    public function main()
    {
        $this->extensions->hk_InitData($this, __FUNCTION__);

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

        $this->data['categories'] = ['' => $this->language->get('text_select_category')];
        $results = Category::getCategories(0, $this->session->data['current_store_id']);
        foreach ($results as $r) {
            $this->data['categories'][$r['category_id']] = $r['name'];
        }

        $grid_settings = [
            'table_id'     => 'product_grid',
            'url'          => $this->html->getSecureURL(
                'listing_grid/product',
                '&category='.(int)$this->request->get['category']
            ),
            'editurl'      => $this->html->getSecureURL('listing_grid/product/update'),
            'update_field' => $this->html->getSecureURL('listing_grid/product/update_field'),
            //NOTE: set default sorting by date_modified, but column not present in the grid
            'sortname'     => 'date_modified',
            'sortorder'    => 'desc',
            'actions'      => [
                'edit'   => [
                    'text'     => $this->language->get('text_edit'),
                    'href'     => $this->html->getSecureURL('catalog/product/update', '&product_id=%ID%'),
                    'children' => array_merge([
                        'quickview'  => [
                            'text'  => $this->language->get('text_quick_view'),
                            'href'  => $this->html->getSecureURL('catalog/product/update', '&modal_mode=1&product_id=%ID%'),
                            //quick view port URL
                            'vhref' => $this->html->getSecureURL(
                                'r/common/viewport/modal',
                                '&modal_mode=1&viewport_rt=catalog/product/update&product_id=%ID%'
                            ),
                        ],
                        'audit_log'  => [
                            'text'  => $this->language->get('text_audit_log'),
                            'href'  => $this->html->getSecureURL('tool/audit_log', '&modal_mode=1&auditable_type=Product&auditable_id=%ID%'),
                            //quick view port URL
                            'vhref' => $this->html->getSecureURL(
                                'r/common/viewport/modal',
                                '&viewport_rt=tool/audit_log&modal_mode=1&auditable_type=Product&auditable_id=%ID%'
                            ),
                        ],
                        'general'    => [
                            'text' => $this->language->get('tab_general'),
                            'href' => $this->html->getSecureURL('catalog/product/update', '&product_id=%ID%'),
                        ],
                        'media'      => [
                            'text' => $this->language->get('tab_media'),
                            'href' => $this->html->getSecureURL('catalog/product_images', '&product_id=%ID%'),
                        ],
                        'options'    => [
                            'text' => $this->language->get('tab_option'),
                            'href' => $this->html->getSecureURL('catalog/product_options', '&product_id=%ID%'),
                        ],
                        'files'      => [
                            'text' => $this->language->get('tab_files'),
                            'href' => $this->html->getSecureURL('catalog/product_files', '&product_id=%ID%'),
                        ],
                        'relations'  => [
                            'text' => $this->language->get('tab_relations'),
                            'href' => $this->html->getSecureURL('catalog/product_relations', '&product_id=%ID%'),
                        ],
                        'promotions' => [
                            'text' => $this->language->get('tab_promotions'),
                            'href' => $this->html->getSecureURL('catalog/product_promotions', '&product_id=%ID%'),
                        ],
                        'layout'     => [
                            'text' => $this->language->get('tab_layout'),
                            'href' => $this->html->getSecureURL('catalog/product_layout', '&product_id=%ID%'),
                        ],

                    ], (array)$this->data['grid_edit_expand']),
                ],
                'save'   => [
                    'text' => $this->language->get('button_save'),
                ],
                'delete' => [
                    'text' => $this->language->get('button_delete'),
                ],
                'clone'  => [
                    'text' => $this->language->get('text_clone'),
                    'href' => $this->html->getSecureURL('catalog/product/copy', '&product_id=%ID%'),
                ],
            ],
        ];

        $grid_settings['colNames'] = [
            '',
            $this->language->get('column_name'),
            $this->language->get('column_model'),
            $this->language->get('column_price'),
            $this->language->get('column_quantity'),
            $this->language->get('column_status'),
        ];
        $grid_settings['colModel'] = [
            [
                'name'     => 'image',
                'index'    => 'image',
                'align'    => 'center',
                'width'    => 65,
                'sortable' => false,
                'search'   => false,
            ],
            [
                'name'  => 'name',
                'index' => 'name',
                'align' => 'center',
                'width' => 200,
            ],
            [
                'name'  => 'model',
                'index' => 'model',
                'align' => 'center',
                'width' => 120,
            ],
            [
                'name'   => 'price',
                'index'  => 'price',
                'align'  => 'center',
                'width'  => 90,
                'search' => false,
            ],
            [
                'name'   => 'quantity',
                'index'  => 'quantity',
                'align'  => 'center',
                'width'  => 90,
                'search' => false,
            ],
            [
                'name'   => 'status',
                'index'  => 'status',
                'align'  => 'center',
                'width'  => 130,
                'search' => false,
            ],
        ];

        $form = new AForm();
        $form->setForm([
            'form_name' => 'product_grid_search',
        ]);

        //get search filter from cookie if required
        $search_params = [];
        if ($this->request->get['saved_list']) {
            $grid_search_form = json_decode(html_entity_decode($this->request->cookie['grid_search_form']));
            if ($grid_search_form->table_id == $grid_settings['table_id']) {
                parse_str($grid_search_form->params, $search_params);
            }
        }

        $grid_search_form = [];
        $grid_search_form['id'] = 'product_grid_search';
        $grid_search_form['form_open'] = $form->getFieldHtml([
            'type'   => 'form',
            'name'   => 'product_grid_search',
            'action' => '',
        ]);
        $grid_search_form['submit'] = $form->getFieldHtml([
            'type'  => 'button',
            'name'  => 'submit',
            'text'  => $this->language->get('button_go'),
            'style' => 'button1',
        ]);
        $grid_search_form['reset'] = $form->getFieldHtml([
            'type'  => 'button',
            'name'  => 'reset',
            'text'  => $this->language->get('button_reset'),
            'style' => 'button2',
        ]);

        $grid_search_form['fields']['keyword'] = $form->getFieldHtml([
            'type'        => 'input',
            'name'        => 'keyword',
            'value'       => $search_params['keyword'],
            'placeholder' => $this->language->get('filter_product'),
        ]);
        $grid_search_form['fields']['match'] = $form->getFieldHtml([
            'type'    => 'selectbox',
            'name'    => 'match',
            'value'   => $search_params['match'],
            'options' => [
                'any'   => $this->language->get('filter_any_word'),
                'all'   => $this->language->get('filter_all_words'),
                'exact' => $this->language->get('filter_exact_match'),
            ],
        ]);

        $grid_search_form['entry_pfrom'] = $this->language->get('filter_price_range');

        $grid_search_form['fields']['pfrom'] = $form->getFieldHtml([
            'type'        => 'input',
            'name'        => 'pfrom',
            'value'       => $search_params['pfrom'],
            'placeholder' => $this->language->get('filter_price_min'),
            'style'       => 'small-field',
        ]);
        $grid_search_form['fields']['pto'] = $form->getFieldHtml([
            'type'        => 'input',
            'name'        => 'pto',
            'value'       => $search_params['pto'],
            'placeholder' => $this->language->get('filter_price_max'),
            'style'       => 'small-field',
        ]);

        if ($this->request->get['category']) {
            $search_params['category'] = $this->request->get['category'];
        }

        $grid_search_form['fields']['category'] = $form->getFieldHtml(
            [
                'type'        => 'selectbox',
                'name'        => 'category',
                'options'     => $this->data['categories'],
                'style'       => 'chosen',
                'value'       => $search_params['category'],
                'placeholder' => $this->language->get('text_select_category'),
            ]);
        $grid_search_form['fields']['status'] = $form->getFieldHtml([
            'type'        => 'selectbox',
            'name'        => 'status',
            'value'       => $search_params['status'],
            'placeholder' => $this->language->get('text_select_status'),
            'options'     => [
                1 => $this->language->get('text_enabled'),
                0 => $this->language->get('text_disabled'),
            ],
        ]);

        $grid_settings['search_form'] = true;

        $grid_settings['multiaction_options']['delete'] = $this->language->get('text_delete_selected');
        $grid_settings['multiaction_options']['save'] = $this->language->get('text_save_selected');
        $grid_settings['multiaction_options']['relate'] = $this->language->get('text_set_related');

        $this->view->assign('relate_selected_url', $grid_settings['editurl']);
        $this->view->assign('text_success_relation_set', $this->language->get('text_success_relation_set'));

        $grid = $this->dispatch('common/listing_grid', [$grid_settings]);
        $this->view->assign('listing_grid', $grid->dispatchGetOutput());
        $this->view->assign('search_form', $grid_search_form);
        $this->view->assign('form_language_switch', $this->html->getContentLanguageSwitcher());
        $this->view->assign('form_store_switch', $this->html->getStoreSwitcher());

        $this->view->assign('insert', $this->html->getSecureURL('catalog/product/insert'));
        $this->view->assign('help_url', $this->gen_help_url('product_listing'));
        $this->processTemplate('pages/catalog/product_list.tpl');

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function insert()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->document->setTitle($this->language->get('heading_title'));
        if ($this->request->is_POST() && $this->validateForm()) {
            try {
                $product_data = $this->prepareData($this->request->post);
                $product = Product::createProduct($product_data);
                $this->data['product_id'] = $product->product_id;
                $this->extensions->hk_ProcessData($this, 'product_insert');
                $this->session->data['success'] = $this->language->get('text_success');
                abc_redirect($this->html->getSecureURL('catalog/product/update', '&product_id='.$product->product_id));
            } catch (\Exception $e) {
                $this->log->error($e->getMessage()."\n".$e->getTraceAsString());
            }
        }

        if (isset($this->data['oldForm']) && $this->data['oldForm'] === true) {
            $this->buildForm();
        } else {
            $this->buildFormNew();
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function update()
    {

        $args = func_get_args();

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->document->setTitle($this->language->get('heading_title'));

        $this->view->assign('error_warning', $this->session->data['warning']);
        if (isset($this->session->data['warning'])) {
            unset($this->session->data['warning']);
        }
        $this->view->assign('success', $this->session->data['success']);
        if (isset($this->session->data['success'])) {
            unset($this->session->data['success']);
        }

        if ($this->request->is_POST() && $this->validateForm()) {
            $product_data = $this->prepareData($this->request->post);
            $product_id = $this->data['product_id'] = (int)$this->request->get['product_id'];

            Product::updateProduct($product_id, $product_data);

            $this->extensions->hk_ProcessData($this, 'product_update');
            $this->session->data['success'] = $this->language->get('text_success');
            abc_redirect($this->html->getSecureURL('catalog/product/update', '&product_id='.$product_id));
        }

        // if (isset($this->data['oldForm']) && $this->data['oldForm'] === true) {
        $this->buildForm($args);
        //} else {
        //    $this->buildFormNew();
        //}

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function buildFormNew()
    {
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->loadLanguage('catalog/product');

        $formData = [
            'url'           => $this->html->getSecureURL('r/catalog/product_form'),
            'back_url'      => $this->html->getSecureURL('catalog/product'),
            'form_name'     => 'product_form',
            'fields_preset' => [
                'default' => [
                    "v_flex_props" => [
                        "xs12" => true,
                    ],
                ],
                'fields'  => [
                    'product_type_id' => [
                        'value'        => $this->request->get['product_type_id'] ? (int) $this->request->get['product_type_id'] : 0,
                        'ajax_params'  => [
                            'relatedTo' => 'product_type_id',
                            'ajax_url'  => $this->html->getSecureURL('r/catalog/product_form'),
                        ],
                        "v_flex_props" => [
                            "xs12" => true,
                        ],
                    ],
                    'date_available'  => [
                        'value'        => date('Y-m-d'),
                    ],
                    'tax_class_id'    => [
                        'value' => 0,
                    ],
                    'product_stores'  => [
                        'value' => 0,
                    ],
                ],
            ],
        ];

        $product_id = (int)$this->request->get['product_id'];
        $product_type_id = null;
        if ($product_id) {

            //$productInfo = Cache::get('product.'.$product_id);

            /**
             * @var Product $productInfo
             */
            $productInfo = $this->productInstance->find($product_id);

            $product = $this->productInstance
                ->with(
                    [
                        'description',
                        'tags',
                        'stores',
                        'attributes' => function($query) use ($productInfo) {
                                            /** @var QueryBuilder $query */
                                            $query->where(
                                                'object_type_id',
                                                '=',
                                                $productInfo->product_type_id
                                            );
                                        }
                    ]
                )
                ->find($product_id);

            if ($product) {
                $product = $product->toArray();
            } else {
                $product = [];
            }

            $productStores = $this->db->table('products_to_stores')
                ->where('product_id', '=', $product_id)
                ->get()->toArray();
            $product['product_store'] = array_column($productStores, 'store_id');

            $productCategories = $productInfo->categories;
            $product['categories'] = [];
            foreach ($productCategories as $productCategory) {
                    $product['categories'][] = $productCategory->category_id;
            }


            $product['keyword'] = UrlAlias::getProductKeyword($product_id, $this->language->getContentLanguageID());
            $product_type_id = $product['product_type_id'];

            foreach ($product as $fieldName => $fieldValue) {
                if (is_array($fieldValue) && $fieldName == 'description') {
                    $product = array_merge($product, $fieldValue);
                }
                if (is_array($fieldValue) && $fieldName == 'tags') {
                    $tags = $fieldValue;
                    $arTags = [];
                    foreach ($tags as $tag) {
                        $arTags[] = $tag['tag'];
                    }
                    $product['tags'] = implode(',', $arTags);
                    unset($arTags, $tags);
                }

                if (is_array($fieldValue) && $fieldName == 'attributes') {
                    $attributes = $fieldValue;
                    foreach ($attributes as $attribute) {
                        $val = json_decode($attribute['attribute_value'], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $attribute['attribute_value'] = $val;
                        }

                        $product[$attribute['attribute_name']] = $attribute['attribute_value'];
                    }
                    unset($product['attributes']);
                }
            }

            $this->data['active'] = 'general';
            //load tabs controller
            $tabs_obj = $this->dispatch('pages/catalog/product_tabs', [$this->data]);
            $this->data['product_tabs'] = $tabs_obj->dispatchGetOutput();
            unset($tabs_obj);
            $this->addChild('pages/catalog/product_summary', 'summary_form', 'pages/catalog/product_summary.tpl');

            foreach ($product as $fieldName => $filedValue) {
                $formData['fields_preset']['fields'][$fieldName]['value'] = $filedValue;
            }
        }

        $form = new FormBuilder(ABC::getFullClassName('Product'), $product_type_id, $formData);
        $this->data['form'] = $form->getForm()->toArray();

        $transformer = new PHPToJavaScriptTransformer($this->document, 'abc');
        $transformer->put(['form' => $this->data['form']]);

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);


        if (H::has_value($this->request->get['modal_mode'])) {
            $this->data['modal_mode'] = 1;
        }

        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/catalog/product_vue_form.tpl');

    }

    public function copy()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->document->setTitle($this->language->get('heading_title'));
        if (isset($this->request->get['product_id']) && $this->validateCopy()) {
            $this->data['new_product'] = $this->model_catalog_product->copyProduct($this->request->get['product_id']);
            $this->extensions->hk_ProcessData($this, 'product_copy');
            if ($this->data['new_product']) {
                $this->session->data['success'] = sprintf(
                    $this->language->get('text_success_copy'),
                    $this->data['new_product']['name']
                );
                if ($this->data['new_product']['layout_clone']) {
                    $this->session->data['success'] .= ' '.$this->language->get('text_success_copy_layout');
                }
                abc_redirect($this->html->getSecureURL(
                    'catalog/product/update',
                    '&product_id='.$this->data['new_product']['id']));
            } else {
                $this->session->data['success'] = $this->language->get('text_error_copy');
                abc_redirect($this->html->getSecureURL('catalog/product'));
            }
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
        abc_redirect($this->html->getSecureURL('catalog/product'));
    }

    protected function buildForm($args = [])
    {
        $product_id = (int)$this->request->get['product_id'];

        $viewport_mode = isset($args[0]['viewport_mode']) ? $args[0]['viewport_mode'] : '';
        $content_language_id = $this->language->getContentLanguageID();

        $product_info = Product::getProductInfo($product_id);
        if(!$product_info){
           abc_redirect($this->html->getSecureURL('catalog/product'));
        }

        if ($this->request->post) {
            $this->data = array_merge($this->data, $this->request->post);
        } elseif (isset($product_info)) {
            $this->data = array_merge($this->data, $product_info);
        }

        $this->data['heading_title'] = $this->language->get('heading_title');
        $this->data['error'] = $this->error;
        $this->data['cancel'] = $this->html->getSecureURL('catalog/product');

        $this->loadLanguage('catalog/object_type');

        $this->document->initBreadcrumb([
            'href'      => $this->html->getSecureURL('index/home'),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);
        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('catalog/product'),
            'text'      => $this->language->get('heading_title'),
            'separator' => false,
        ]);
        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('catalog/product'),
            'text'      =>
                ($product_id
                    ? $this->language->get('text_edit').'&nbsp;'
                    .$this->language->get('text_product').' - '.$this->data['name']
                    : $this->language->get('text_insert')
                ),
            'separator' => ' :: ',
            'current'   => true,
        ]);

        $stores = Store::all()->toArray();
        $this->data['stores'] = ['' => $this->language->get('text_default')];

        $this->data['stores'] += array_column($stores, 'name', 'store_id');

        $manufacturers = Manufacturer::all()->toArray();
        $this->data['manufacturers'] = [0 => $this->language->get('text_none')];
        $this->data['manufacturers'] += array_column($manufacturers, 'name', 'manufacturer_id');


        $this->loadModel('localisation/stock_status');
        $this->data['stock_statuses'] = ['0' => $this->language->get('text_none')];
        $results = $this->model_localisation_stock_status->getStockStatuses();
        foreach ($results as $r) {
            $this->data['stock_statuses'][$r['stock_status_id']] = $r['name'];
        }

        $this->loadModel('localisation/tax_class');
        $this->data['tax_classes'] = [0 => $this->language->get('text_none')];
        $results = $this->model_localisation_tax_class->getTaxClasses();
        foreach ($results as $r) {
            $this->data['tax_classes'][$r['tax_class_id']] = $r['title'];
        }

        $this->loadModel('localisation/weight_class');
        $this->data['weight_classes'] = [];
        $results = $this->model_localisation_weight_class->getWeightClasses();
        foreach ($results as $r) {
            $this->data['weight_classes'][$r['weight_class_id']] = $r['title'];
        }

        $this->loadModel('localisation/length_class');
        $this->data['length_classes'] = [];
        $results = $this->model_localisation_length_class->getLengthClasses();
        foreach ($results as $r) {
            $this->data['length_classes'][$r['length_class_id']] = $r['title'];
        }

        $productTypes = ObjectType::where('object_type', 'Product')
            ->with('description')
            ->where('status', 1)
            ->get()
            ->toArray();

        $this->data['product_types'][] = $this->language->get('text_select');
        foreach ($productTypes as $productType) {
            $this->data['product_types'][$productType['object_type_id']] = $productType['description']['name'];
        }


        if (isset($this->request->post['product_category'])) {
            $this->data['product_category'] = $this->request->post['product_category'];
        } elseif (isset($product_info)) {
            $this->data['product_category'] = array_column($product_info['categories'], 'category_id');
        } else {
            $this->data['product_category'] = [];
        }

        if (isset($this->request->post['product_store'])) {
            $this->data['product_store'] = $this->request->post['product_store'];
        } elseif (isset($product_info) && !empty($product_info)) {
            $this->data['product_store'] = array_column($product_info['stores'], 'store_id');
        } else {
            $this->data['product_store'] = [0];
        }

        if (isset($this->request->post['product_description'])) {
            $this->data['product_description'] = $this->request->post['product_description'];
        } elseif (isset($product_info)) {
            $this->data['product_description'] = $this->model_catalog_product->getProductDescriptions(
                $product_id,
                $content_language_id
            );
        } else {
            $this->data['product_description'] = [];
        }

        if (isset($this->request->post['featured'])) {
            $this->data['featured'] = $this->request->post['featured'];
        } elseif (isset($product_info)) {
            $this->data['featured'] = $product_info['featured'];
        } else {
            $this->data['featured'] = 0;
        }

        if (isset($this->request->post['product_tags'])) {
            $this->data['product_tags'] = $this->request->post['product_tags'];
        } elseif (isset($product_info)) {
            $this->data['product_tags'] = $product_info['tags_by_language']
                ? implode(",", array_column($product_info['tags_by_language'], 'tag'))
                : '';
        } else {
            $this->data['product_tags'] = '';
        }
        $this->loadModel('tool/image');
        if (isset($product_info)
            && $product_info['image']
            && file_exists(ABC::env('DIR_IMAGES').$product_info['image'])
        ) {
            $this->data['preview'] = $this->model_tool_image->resize($product_info['image'], 100, 100);
        } else {
            $this->data['preview'] = $this->model_tool_image->resize('no_image.jpg', 100, 100);
        }

        if (!H::has_value($this->data['stock_status_id'])) {
            $this->data['stock_status_id'] = $this->config->get('config_stock_status_id');
        }
        if (isset($this->request->post['date_available'])) {
            $this->data['date_available'] = $this->request->post['date_available'];
        } elseif (isset($product_info)) {
            $this->data['date_available'] = $product_info['date_available'];
        } else {
            $this->data['date_available'] = H::dateInt2ISO(time() - 86400);
        }

        $weight_info = $this->model_localisation_weight_class->getWeightClassDescriptionByUnit(
            $this->config->get('config_weight_class')
        );
        if (isset($this->request->post['weight_class_id'])) {
            $this->data['weight_class_id'] = $this->request->post['weight_class_id'];
        } elseif (isset($product_info)) {
            $this->data['weight_class_id'] = $product_info['weight_class_id'];
        } elseif (isset($weight_info)) {
            $this->data['weight_class_id'] = $weight_info['weight_class_id'];
        } else {
            $this->data['weight_class_id'] = '';
        }

        $length_info = $this->model_localisation_length_class->getLengthClassDescriptionByUnit(
            $this->config->get('config_length_class')
        );
        if (isset($this->request->post['length_class_id'])) {
            $this->data['length_class_id'] = $this->request->post['length_class_id'];
        } elseif (isset($product_info)) {
            $this->data['length_class_id'] = $product_info['length_class_id'];
        } elseif (isset($length_info)) {
            $this->data['length_class_id'] = $length_info['length_class_id'];
        } else {
            $this->data['length_class_id'] = '';
        }

        if ($this->data['status'] === null) {
            $this->data['status'] = 1;
        }
        if ($this->data['quantity'] === null) {
            $this->data['quantity'] = 1;
        }
        if ($this->data['minimum'] == null) {
            $this->data['minimum'] = 1;
        }
        if ($this->data['sort_order'] === null) {
            $this->data['sort_order'] = 1;
        }

        $this->data['active'] = 'details';
        if (!isset($product_id)) {
            $this->data['action'] = $this->html->getSecureURL('catalog/product/insert');
            $this->data['form_title'] = $this->language->get('text_insert').$this->language->get('text_product');
            $this->data['update'] = '';
            $form = new AForm('ST');
            $this->data['summary_form'] = '';
        } else {
            $this->data['action'] = $this->html->getSecureURL('catalog/product/update', '&product_id='.$product_id);
            $this->data['form_title'] = $this->language->get('text_edit').'&nbsp;'.$this->language->get('text_product');
            $this->data['update'] = $this->html->getSecureURL('listing_grid/product/update_field', '&id='.$product_id);
            $form = new AForm('HS');

            $this->data['active'] = 'general';
            //load tabs controller
            $tabs_obj = $this->dispatch('pages/catalog/product_tabs', [$this->data]);
            $this->data['product_tabs'] = $tabs_obj->dispatchGetOutput();
            unset($tabs_obj);
            $this->addChild('pages/catalog/product_summary', 'summary_form', 'pages/catalog/product_summary.tpl');
        }

        $form->setForm([
            'form_name' => 'productFrm',
            'update'    => $this->data['update'],
        ]);

        $this->data['form']['id'] = 'productFrm';
        $this->data['form']['form_open'] = $form->getFieldHtml([
            'type'   => 'form',
            'name'   => 'productFrm',
            'action' => $this->data['action'],
            'attr'   => 'data-confirm-exit="true" class="aform form-horizontal"',
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

        $this->data['form']['fields']['general']['status'] = $form->getFieldHtml([
            'type'  => 'checkbox',
            'name'  => 'status',
            'value' => $this->data['status'],
            'style' => 'btn_switch btn-group-sm',
        ]);

        $this->data['form']['fields']['general']['featured'] = $form->getFieldHtml([
            'type'  => 'checkbox',
            'name'  => 'featured',
            'value' => $this->data['featured'],
            'style' => 'btn_switch btn-group-sm',
        ]);

        $this->data['form']['fields']['general']['name'] = $form->getFieldHtml([
            'type'         => 'input',
            'name'         => 'name',
            'value'        => $this->data['name'],
            'required'     => true,
            'multilingual' => true,
        ]);

        $this->data['form']['fields']['general']['blurb'] = $form->getFieldHtml([
            'type'         => 'textarea',
            'name'         => 'blurb',
            'value'        => $this->data['blurb'],
            'multilingual' => true,
        ]);

        if ($viewport_mode != 'modal') {
            $this->data['form']['fields']['general']['description'] = $form->getFieldHtml([
                'type'         => 'texteditor',
                'name'         => 'description',
                'value'        => $this->data['description'],
                'multilingual' => true,
            ]);
        }

        $this->data['form']['fields']['general']['meta_keywords'] = $form->getFieldHtml([
            'type'         => 'textarea',
            'name'         => 'meta_keywords',
            'value'        => $this->data['meta_keywords'],
            'multilingual' => true,
        ]);

        $this->data['form']['fields']['general']['meta_description'] = $form->getFieldHtml([
            'type'         => 'textarea',
            'name'         => 'meta_description',
            'value'        => $this->data['meta_description'],
            'multilingual' => true,
        ]);

        $this->data['form']['fields']['general']['tags'] = $form->getFieldHtml([
            'type'  => 'input',
            'name'  => 'product_tags',
            'value' => $this->data['product_tags'],
        ]);

        $results = Category::getCategories(0, $this->session->data['current_store_id']);
        $this->data['categories'] =  array_column($results, 'name', 'category_id');

        $this->data['form']['fields']['general']['category'] = $form->getFieldHtml([
            'type'        => 'checkboxgroup',
            'name'        => 'product_category[]',
            'value'       => $this->data['product_category'],
            'options'     => $this->data['categories'],
            'style'       => 'chosen',
            'placeholder' => $this->language->get('text_select_category'),
        ]);

        $this->data['form']['fields']['general']['store'] = $form->getFieldHtml([
            'type'        => 'checkboxgroup',
            'name'        => 'product_store[]',
            'value'       => $this->data['product_store'],
            'options'     => $this->data['stores'],
            'style'       => 'chosen',
            'placeholder' => $this->language->get('entry_store'),
        ]);

        $this->data['form']['fields']['data']['manufacturer'] = $form->getFieldHtml([
            'type'        => 'selectbox',
            'name'        => 'manufacturer_id',
            'value'       => $this->data['manufacturer_id'],
            'options'     => $this->data['manufacturers'],
            'style'       => 'chosen',
            'placeholder' => $this->language->get('entry_manufacturer'),
        ]);

        $this->data['form']['fields']['data']['model'] = $form->getFieldHtml([
            'type'     => 'input',
            'name'     => 'model',
            'value'    => $this->data['model'],
            'required' => false,
        ]);

        $this->data['form']['fields']['data']['call_to_order'] = $form->getFieldHtml([
            'type'  => 'checkbox',
            'name'  => 'call_to_order',
            'value' => $this->data['call_to_order'],
            'style' => 'btn_switch btn-group-sm',
        ]);

        $this->data['form']['fields']['data']['price'] = $form->getFieldHtml([
            'type'  => 'input',
            'name'  => 'price',
            'value' => $this->data['price'],
            'style' => 'small-field',
        ]);
        $this->data['form']['fields']['data']['cost'] = $form->getFieldHtml([
            'type'  => 'input',
            'name'  => 'cost',
            'value' => round($this->data['cost'], 3),
            'style' => 'small-field',
        ]);
        $this->data['form']['fields']['data']['tax_class'] = $form->getFieldHtml([
            'type'     => 'selectbox',
            'name'     => 'tax_class_id',
            'value'    => $this->data['tax_class_id'],
            'options'  => $this->data['tax_classes'],
            'help_url' => $this->gen_help_url('tax_class'),
            'style'    => 'medium-field',
        ]);
        $this->data['form']['fields']['data']['subtract'] = $form->getFieldHtml([
            'type'     => 'selectbox',
            'name'     => 'subtract',
            'value'    => $this->data['subtract'],
            'options'  => [
                1 => $this->language->get('text_yes'),
                0 => $this->language->get('text_no'),
            ],
            'help_url' => $this->gen_help_url('product_inventory'),
            'style'    => 'medium-field',
            'disabled' => ($product_info['has_track_options'] ? true : false),
        ]);

        $this->data['form']['fields']['data']['quantity'] = $form->getFieldHtml([
            'type'     => 'input',
            'name'     => 'quantity',
            'value'    => (int)$this->data['quantity'],
            'style'    => 'col-xs-1 small-field',
            'help_url' => $this->gen_help_url('product_inventory'),
            'attr'     => ($product_info['has_track_options'] ? 'disabled' : ''),
        ]);

        $this->data['form']['fields']['data']['minimum'] = $form->getFieldHtml([
            'type'  => 'input',
            'name'  => 'minimum',
            'value' => (int)$this->data['minimum'],
            'style' => 'small-field',
        ]);

        $this->data['form']['fields']['data']['maximum'] = $form->getFieldHtml([
            'type'  => 'input',
            'name'  => 'maximum',
            'value' => (int)$this->data['maximum'],
            'style' => 'small-field',
        ]);

        $this->data['form']['fields']['data']['stock_checkout'] = $form->getFieldHtml([
            'type'    => 'selectbox',
            'name'    => 'stock_checkout',
            'value'   => (H::has_value($this->data['stock_checkout']) ? $this->data['stock_checkout'] : ''),
            'options' => [
                '' => $this->language->get('text_default'),
                0  => $this->language->get('text_no'),
                1  => $this->language->get('text_yes'),
            ],
            'style'   => 'small-field',
        ]);

        $this->data['form']['fields']['data']['stock_status'] = $form->getFieldHtml([
            'type'     => 'selectbox',
            'name'     => 'stock_status_id',
            'value'    => (
            H::has_value($this->data['stock_status_id'])
                ? (int)$this->data['stock_status_id']
                : $this->config->get('config_stock_status_id')
            ),
            'options'  => $this->data['stock_statuses'],
            'help_url' => $this->gen_help_url('product_inventory'),
            'style'    => 'small-field',
        ]);

        $this->data['form']['fields']['data']['sku'] = $form->getFieldHtml([
            'type'  => 'input',
            'name'  => 'sku',
            'value' => $this->data['sku'],
        ]);

        $this->data['form']['fields']['data']['location'] = $form->getFieldHtml([
            'type'  => 'input',
            'name'  => 'location',
            'value' => $this->data['location'],
        ]);
        //prepend button to generate keyword
        $this->data['keyword_button'] = $form->getFieldHtml([
            'type'  => 'button',
            'name'  => 'generate_seo_keyword',
            'text'  => $this->language->get('button_generate'),
            //set button not to submit a form
            'attr'  => 'type="button"',
            'style' => 'btn btn-info',
        ]);
        $this->data['generate_seo_url'] = $this->html->getSecureURL(
            'common/common/getseokeyword',
            '&object_key_name=product_id&id='.$product_id
        );

        $this->data['form']['fields']['data']['keyword'] = $form->getFieldHtml([
            'type'         => 'input',
            'name'         => 'keyword',
            'value'        => $this->data['keyword'],
            'help_url'     => $this->gen_help_url('seo_keyword'),
            'attr'         => ' gen-value="'.H::SEOEncode($this->data['name']).'" ',
            'multilingual' => true,
        ]);
        $this->data['form']['fields']['data']['date_available'] = $form->getFieldHtml([
            'type'       => 'date',
            'name'       => 'date_available',
            'value'      => H::dateISO2Display($this->data['date_available']),
            'default'    => H::dateNowDisplay(),
            'dateformat' => H::format4Datepicker($this->language->get('date_format_short')),
            'highlight'  => 'future',
            'style'      => 'small-field',
        ]);

        $this->data['form']['fields']['data']['sort_order'] = $form->getFieldHtml([
            'type'  => 'input',
            'name'  => 'sort_order',
            'value' => $this->data['sort_order'],
            'style' => 'tiny-field',
        ]);

        $this->data['form']['fields']['data']['shipping'] = $form->getFieldHtml([
            'type'  => 'checkbox',
            'name'  => 'shipping',
            'style' => 'btn_switch btn-group-sm',
            'value' => isset($this->data['shipping']) ? $this->data['shipping'] : 1,
        ]);

        $this->data['form']['fields']['data']['free_shipping'] = $form->getFieldHtml([
            'type'  => 'checkbox',
            'name'  => 'free_shipping',
            'style' => 'btn_switch btn-group-sm',
            'value' => isset($this->data['free_shipping']) ? $this->data['free_shipping'] : 0,
        ]);

        $this->data['form']['fields']['data']['ship_individually'] = $form->getFieldHtml([
            'type'  => 'checkbox',
            'name'  => 'ship_individually',
            'style' => 'btn_switch btn-group-sm',
            'value' => isset($this->data['ship_individually']) ? $this->data['ship_individually'] : 0,
        ]);

        $this->data['form']['fields']['data']['shipping_price'] = $form->getFieldHtml([
            'type'  => 'input',
            'name'  => 'shipping_price',
            'value' => round($this->data['shipping_price'], 3),
            'style' => 'tiny-field',
        ]);

        $this->data['form']['fields']['data']['length'] = $form->getFieldHtml([
            'type'  => 'input',
            'name'  => 'length',
            'value' => $this->data['length'],
            'style' => 'tiny-field',
        ]);
        $this->data['form']['fields']['data']['width'] = $form->getFieldHtml([
            'type'  => 'input',
            'name'  => 'width',
            'value' => $this->data['width'],
            'attr'  => ' autocomplete="false"',
            'style' => 'tiny-field',
        ]);
        $this->data['form']['fields']['data']['height'] = $form->getFieldHtml([
            'type'  => 'input',
            'name'  => 'height',
            'value' => $this->data['height'],
            'attr'  => ' autocomplete="false"',
            'style' => 'tiny-field',
        ]);

        if ($product_id && !$this->data['length_class_id']) {
            $this->data['length_classes'][0] = $this->language->get('text_none');
        }

        $this->data['form']['fields']['data']['length_class'] = $form->getFieldHtml([
            'type'    => 'selectbox',
            'name'    => 'length_class_id',
            'value'   => $this->data['length_class_id'],
            'options' => $this->data['length_classes'],
            'style'   => 'small-field',
        ]);

        if ($product_id
            && $this->data['shipping']
            && (!(float)$this->data['weight'] || !$this->data['weight_class_id'])
            && !(float)$this->data['shipping_price']
        ) {
            if (!$this->data['weight_class_id']) {
                $this->data['error']['weight_class'] = $this->language->get('error_weight_class');
            }
            if (!(float)$this->data['weight']) {
                $this->data['error']['weight'] = $this->language->get('error_weight_value');
            }
        }

        if ($product_id && !$this->data['weight_class_id']) {
            $this->data['weight_classes'][0] = $this->language->get('text_none');
        }

        $this->data['form']['fields']['data']['weight'] = $form->getFieldHtml([
            'type'  => 'input',
            'name'  => 'weight',
            'value' => $this->data['weight'],
            'attr'  => ' autocomplete="false"',
            'style' => 'tiny-field',
        ]);

        $this->data['form']['fields']['data']['weight_class'] = $form->getFieldHtml([
            'type'    => 'selectbox',
            'name'    => 'weight_class_id',
            'value'   => $this->data['weight_class_id'],
            'options' => $this->data['weight_classes'],
            'style'   => 'small-field',
        ]);

        $this->data['product_id'] = $product_id;
        if ($product_id && $this->config->get('config_embed_status')) {
            $this->data['embed_url'] = $this->html->getSecureURL('common/do_embed/product', '&product_id='.$product_id);
        }

        $this->data['text_clone'] = $this->language->get('text_clone');
        $this->data['clone_url'] = $this->html->getSecureURL(
            'catalog/product/copy',
            '&product_id='.$this->request->get['product_id']
        );
        $this->data['form_language_switch'] = $this->html->getContentLanguageSwitcher();
        $this->data['language_id'] = $content_language_id;
        $this->data['language_code'] = $this->session->data['language'];
        $this->data['help_url'] = $this->gen_help_url('product_edit');
        $saved_list_data = json_decode(html_entity_decode($this->request->cookie['grid_params']));
        if ($saved_list_data->table_id == 'product_grid') {
            $this->data['list_url'] = $this->html->getSecureURL('catalog/product', '&saved_list=product_grid');
        }

        if ($viewport_mode == 'modal') {
            $tpl = 'responses/viewport/modal/catalog/product_form.tpl';
        } else {
            $this->addChild(
                'responses/common/resource_library/get_resources_html',
                'resources_html',
                'responses/common/resource_library_scripts.tpl'
            );
            $resources_scripts = $this->dispatch(
                'responses/common/resource_library/get_resources_scripts',
                [
                    'object_name' => '',
                    'object_id'   => '',
                    'types'       => ['image'],
                ]
            );
            $this->data['resources_scripts'] = $resources_scripts->dispatchGetOutput();
            $this->data['rl'] = $this->html->getSecureURL(
                'common/resource_library',
                '&action=list_library&object_name=&object_id&type=image&mode=single'
            );

            $tpl = 'pages/catalog/product_form.tpl';
        }

        $this->view->batchAssign($this->data);
        $this->processTemplate($tpl);
    }

    protected function validateForm()
    {

        if (!$this->user->canModify('catalog/product')) {
            $this->error['warning'] = $this->language->get_error('error_permission');
        }
        $len = mb_strlen($this->request->post['name']);
        if ($len < 1 || $len > 255) {
            $this->error['name'] = $this->language->get_error('error_name');
        }

        if (mb_strlen($this->request->post['model']) > 64) {
            $this->error['model'] = $this->language->get_error('error_model');
        }

        if (($error_text = $this->html->isSEOkeywordExists(
            'product_id='.$this->request->get['product_id'],
            $this->request->post['keyword']))
        ) {
            $this->error['keyword'] = $error_text;
        }

        foreach (['length', 'width', 'height', 'weight'] as $name) {
            $v = abs(H::preformatFloat($this->request->post[$name], $this->language->get('decimal_point')));
            if ($v >= 1000) {
                $this->error[$name] = $this->language->get('error_measure_value');
            }
        }

        $this->extensions->hk_ValidateData($this, __FUNCTION__);

        if (!$this->error) {
            return true;
        } else {
            if (!isset($this->error['warning'])) {
                $this->error['warning'] = $this->language->get_error('error_required_data');
            }
            return false;
        }
    }

    protected function validateCopy()
    {
        if (!$this->user->canModify('catalog/product')) {
            $this->error['warning'] = $this->language->get_error('error_permission');
        }

        $this->extensions->hk_ValidateData($this, __FUNCTION__);

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }

    protected function prepareData($data = [])
    {
        if (isset($data['date_available'])) {
            $data['date_available'] = H::dateDisplay2ISO($data['date_available']);
        }
        if (!isset($data['language_id'])) {
            $data['language_id'] = $this->language->getContentLanguageID();
        }
        $data['product_category'] = $data['product_category'] ?: [];
        return $data;
    }
}
