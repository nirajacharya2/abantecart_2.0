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
use abc\core\engine\AResource;
use abc\core\engine\Registry;
use abc\core\lib\ACurrency;
use abc\core\lib\AException;
use abc\core\lib\LibException;
use abc\models\catalog\Category;
use abc\models\catalog\Product;
use abc\models\customer\Address;
use abc\models\customer\Customer;
use abc\models\customer\CustomerGroup;
use abc\models\customer\CustomerTransaction;
use abc\models\locale\Currency;
use abc\models\order\Order;
use abc\models\order\OrderDownload;
use abc\models\order\OrderHistory;
use abc\models\order\OrderProduct;
use abc\models\order\OrderStatus;
use abc\models\order\OrderStatusDescription;
use abc\models\order\OrderTotal;
use abc\modules\events\ABaseEvent;
use abc\modules\traits\SaleOrderTrait;
use H;
use Illuminate\Validation\ValidationException;

/**
 * Class ControllerPagesSaleOrder
 *
 * @package abc\controllers\admin
 */
class ControllerPagesSaleOrder extends AController
{
    use SaleOrderTrait;
    public $data = [];
    public $error = [];

    public function main()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->document->setTitle($this->language->get('heading_title'));
        $this->document->initBreadcrumb([
            'href'      => $this->html->getSecureURL('index/home'),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);
        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('sale/order'),
            'text'      => $this->language->get('heading_title'),
            'separator' => ' :: ',
            'current'   => true,
        ]);

        if (isset($this->session->data['error'])) {
            $this->data['error_warning'] = $this->session->data['error'];
            unset($this->session->data['error']);
        } elseif (isset($this->error['warning'])) {
            $this->data['error_warning'] = $this->error['warning'];
        } else {
            $this->data['error_warning'] = '';
        }

        if (isset($this->session->data['success'])) {
            $this->data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $this->data['success'] = '';
        }

        //set content language to main language.
        if ($this->language->getContentLanguageID() != $this->language->getLanguageID()) {
            //reset content language
            $this->language->setCurrentContentLanguage($this->language->getLanguageID());
        }

        //outer parameters to filter the result
        $extra_params = '';
        $extra_params .= $this->request->get['customer_id'] ? '&customer_id='.$this->request->get['customer_id'] : '';
        $extra_params .= $this->request->get['product_id'] ? '&product_id='.$this->request->get['product_id'] : '';
        $extra_params .= $this->request->get['status'] ? '&status='.$this->request->get['status'] : '';

        $grid_settings = [
            //id of grid
            'table_id'     => 'order_grid',
            // url to load data from
            'url'          => $this->html->getSecureURL('listing_grid/order', $extra_params),
            'editurl'      => $this->html->getSecureURL('listing_grid/order/update'),
            'update_field' => $this->html->getSecureURL('listing_grid/order/update_field'),
            'sortname'     => 'order_id',
            'sortorder'    => 'desc',
            'multiselect'  => 'true',
            // actions
            'actions'      => [
                'view' => [
                    'text'  => $this->language->get('text_quick_view'),
                    'href'  => $this->html->getSecureURL('sale/order/details',
                        '&order_id=%ID%'),
                    //quick view port URL
                    'vhref' => $this->html->getSecureURL(
                        'r/common/viewport/modal',
                        '&viewport_rt=sale/order/details&order_id=%ID%'
                    ),
                ],
                'tracking' => [
                    'text'  => $this->language->get('text_tracking_products'),
                    'href'  => $this->html->getSecureURL(
                        'sale/order/details',
                        '&order_id=%ID%'
                    ),
                    //quick view port URL
                    'vhref' => $this->html->getSecureURL(
                        'r/sale/order_tracking/products&order_id=%ID%'
                    ),
                ],

                'edit'   => [
                    'text'     => $this->language->get('text_edit'),
                    'href'     => $this->html->getSecureURL('sale/order/details', '&order_id=%ID%'),
                    'children' => array_merge([
                        'details'   => [
                            'text' => $this->language->get('tab_order_details'),
                            'href' => $this->html->getSecureURL(
                                'sale/order/details',
                                '&order_id=%ID%'
                            ),
                        ],
                        'shipping'  => [
                            'text' => $this->language->get('tab_shipping'),
                            'href' => $this->html->getSecureURL(
                                'sale/order/shipping',
                                '&order_id=%ID%'
                            ),
                        ],
                        'payment'   => [
                            'text' => $this->language->get('tab_payment'),
                            'href' => $this->html->getSecureURL(
                                'sale/order/payment',
                                '&order_id=%ID%'
                            ),
                        ],
                        'files'     => [
                            'text' => $this->language->get('tab_files'),
                            'href' => $this->html->getSecureURL(
                                'sale/order/files',
                                '&order_id=%ID%'
                            ),
                        ],
                        'history'   => [
                            'text' => $this->language->get('tab_history'),
                            'href' => $this->html->getSecureURL(
                                'sale/order/history',
                                '&order_id=%ID%'
                            ),
                        ],

                    ], (array)$this->data['grid_edit_expand']),
                ],
                'print'  => [
                    'text'   => $this->language->get('button_invoice'),
                    'href'   => $this->html->getSecureURL('sale/invoice', '&order_id=%ID%'),
                    'target' => '_invoice',
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
            $this->language->get('column_order'),
            $this->language->get('column_name'),
            $this->language->get('column_status'),
            $this->language->get('column_date_added'),
            $this->language->get('column_total'),
        ];
        $grid_settings['colModel'] = [
            [
                'name'  => 'order_id',
                'index' => 'order_id',
                'width' => 40,
                'align' => 'center',
            ],
            [
                'name'  => 'name',
                'index' => 'name',
                'width' => 90,
                'align' => 'left',
            ],
            [
                'name'   => 'status',
                'index'  => 'status',
                'width'  => 90,
                'align'  => 'center',
                'search' => false,
            ],
            [
                'name'   => 'date_added',
                'index'  => 'date_added',
                'width'  => 90,
                'align'  => 'center',
                'search' => false,
            ],
            [
                'name'  => 'total',
                'index' => 'total',
                'width' => 55,
                'align' => 'center',
            ],
        ];

        $results = OrderStatus::with('description')
                              ->where('display_status', '=', '1')
                              ->get();
        $statuses = [
            'default' => $this->language->get('text_select_status'),
            'all'     => $this->language->get('text_all_orders'),
        ];
        foreach ($results->toArray() as $item) {
            $statuses[$item['order_status_id']] = $item['description']['name'];
        }

        $form = new AForm();
        $form->setForm([
            'form_name' => 'order_grid_search',
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
        $grid_search_form['id'] = 'order_grid_search';
        $grid_search_form['form_open'] = $form->getFieldHtml(
            [
                'type'   => 'form',
                'name'   => 'order_grid_search',
                'action' => '',
            ]
        );
        $grid_search_form['submit'] = $form->getFieldHtml(
            [
                'type'  => 'button',
                'name'  => 'submit',
                'text'  => $this->language->get('button_go'),
                'style' => 'button1',
            ]
        );
        $grid_search_form['reset'] = $form->getFieldHtml(
            [
                'type'  => 'button',
                'name'  => 'reset',
                'text'  => $this->language->get('button_reset'),
                'style' => 'button2',
            ]
        );

        if ($search_params['status'] === null || $search_params['status'] === '') {
            $search_params['status'] = 'default';
        }
        $grid_search_form['fields']['status'] = $form->getFieldHtml(
            [
                'type'    => 'selectbox',
                'name'    => 'status',
                'options' => $statuses,
                'value'   => ($this->request->get['status'] ?: $search_params['status']),
            ]
        );
        $this->view->assign('js_date_format', H::format4Datepicker($this->language->get('date_format_short')));
        $grid_search_form['fields']['date_start'] = $form->getFieldHtml([
            'type'    => 'date',
            'name'    => 'date_start',
            'attr' => 'placeholder="'.$this->language->get('date_from').'"',
        ]);

        $grid_search_form['fields']['date_end'] = $form->getFieldHtml([
            'type'    => 'date',
            'name'    => 'date_end',
            'attr' => 'placeholder="'.$this->language->get('date_to').'"',
        ]);
        $grid_settings['search_form'] = true;

        $grid = $this->dispatch('common/listing_grid', [$grid_settings]);
        $this->view->assign('listing_grid', $grid->dispatchGetOutput());
        $this->view->assign('search_form', $grid_search_form);
        $this->view->assign('help_url', $this->gen_help_url('order_listing'));
        $this->view->assign('form_store_switch', $this->html->getStoreSwitcher());

        $this->document->setTitle($this->language->get('heading_title'));

        $this->processTemplate('pages/sale/order_list.tpl');

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function details()
    {

        $args = func_get_args();
        $viewport_mode = isset($args[0]['viewport_mode']) ? $args[0]['viewport_mode'] : '';

        $this->data = [
            'fields' =>
                [
                    'email',
                    'telephone',
                    'fax',
                    'shipping_method',
                    'payment_method',
                ],
        ];

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->document->setTitle($this->language->get('heading_title'));

        if (H::has_value($this->session->data['error'])) {
            $this->data['error']['warning'] = $this->session->data['error'];
            unset($this->session->data['error']);
        }

        if (isset($this->request->get['order_id'])) {
            $order_id = (int)$this->request->get['order_id'];
        } else {
            $order_id = 0;
        }

        $order_info = Order::getOrderArray($order_id, 'any');

        $post = $this->request->post;
        $post['order_status_id'] = $order_info['order_status_id'];

        if ($this->request->is_POST() && $this->validateDetailsForm($order_id, $post)) {
            try {
                Order::editOrder($order_id, $this->request->post);
            } catch (AException $e) {
                $this->session->data['error'] = $e->getMessage();
            }

            if (H::has_value($this->request->post['downloads'])) {
                $data = $this->request->post['downloads'];
                $this->loadModel('catalog/download');
                foreach ($data as $order_download_id => $item) {
                    if ($item['expire_date']) {
                        $item['expire_date'] = H::dateDisplay2ISO($item['expire_date'],
                            $this->language->get('date_format_short'));
                    } else {
                        $item['expire_date'] = '';
                    }
                    $this->model_catalog_download->editOrderDownload($order_download_id, $item);
                }
            }
            abc_redirect($this->html->getSecureURL('sale/order/details', '&order_id='.$order_id));
        }

        if ($this->error) {
            $this->session->data['error'] = implode(' ', $this->error);
            abc_redirect($this->html->getSecureURL('sale/order/details', '&order_id='.$order_id));
        }

        $this->data['order_info'] = $order_info;

        //set content language to order language ID.
        if ($this->language->getContentLanguageID() != $order_info['language_id']) {
            //reset content language
            $this->language->setCurrentContentLanguage($order_info['language_id']);
        }

        if (empty($order_info)) {
            $this->session->data['error'] = $this->language->get('error_order_load');
            abc_redirect($this->html->getSecureURL('sale/order'));
        }

        if (!$order_info['customer_id']) {
            //if guest checkout - do not check balance system enabling to unblock saving
            $this->data['balance_disabled'] = false;
        } else {
            $this->data['balance_disabled'] = (!$this->config->get('balance_status'));
        }

        $this->document->initBreadcrumb([
            'href'      => $this->html->getSecureURL('index/home'),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);

        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('sale/order'),
            'text'      => $this->language->get('heading_title'),
            'separator' => ' :: ',
        ]);
        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('sale/order/details', '&order_id='.$order_id),
            'text'      => $this->language->get('heading_title').' #'.$order_id,
            'separator' => ' :: ',
            'current'   => true,
        ]);

        if (isset($this->session->data['attention'])) {
            $this->data['attention'] = $this->session->data['attention'];
            unset($this->session->data['attention']);
        } else {
            $this->data['attention'] = '';
        }
        if (isset($this->session->data['success'])) {
            $this->data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $this->data['success'] = '';
        }

        $this->data['heading_title'] = $this->language->get('heading_title').' #'.$order_id;
        $this->data['token'] = $this->session->data['token'];
        $this->data['invoice_url'] = $this->html->getSecureURL('sale/invoice', '&order_id='.$order_id);
        $this->data['button_invoice'] = $this->html->buildElement(
            [
                'type' => 'button',
                'name' => 'generate_invoice',
                'text' => $this->language->get('button_generate'),
            ]
        );
        $this->data['invoice_generate'] = $this->html->getSecureURL('sale/invoice/generate');
        $this->data['category_products'] = $this->html->getSecureURL('product/product/category');
        $this->data['product_update'] = $this->html->getSecureURL('catalog/product/update');
        $this->data['order_id'] = $order_id;
        $this->data['action'] = $this->html->getSecureURL('sale/order/details', '&order_id='.$order_id);
        $this->data['cancel'] = $this->html->getSecureURL('sale/order');

        if ($viewport_mode != 'modal') {
            $this->_initTabs('order_details');
        }

        // These only change for insert, not edit. To be added later
        $this->data['ip'] = $order_info['ip'];
        $this->data['history'] = $this->html->getSecureURL('sale/order/history', '&order_id='.$order_id);
        $this->data['store_name'] = $order_info['store_name'];
        $this->data['store_url'] = $order_info['store_url'];
        $this->data['comment'] = nl2br($order_info['comment']);
        $this->data['firstname'] = $order_info['firstname'];
        $this->data['lastname'] = $order_info['lastname'];
        $this->data['total'] = $this->currency->format(
            $order_info['total'],
            $order_info['currency'],
            $order_info['value']
        );
        $this->data['date_added'] = H::dateISO2Display(
            $order_info['date_added'],
            $this->language->get('date_format_short').' '.$this->language->get('time_format')
        );
        if ($order_info['customer_id']) {
            $this->data['customer_href'] = $this->html->getSecureURL(
                'sale/customer/update',
                '&customer_id='.$order_info['customer_id']
            );
            $this->data['customer_vhref'] = $this->html->getSecureURL(
                'r/common/viewport/modal',
                '&viewport_rt=sale/customer/update&customer_id='.$order_info['customer_id']
            );
        }

        $this->data['order_status'] = OrderStatusDescription::where(
            [
                'language_id'     => $this->language->getContentLanguageID(),
                'order_status_id' => $order_info['order_status_id'],
            ]
        )->first()->name;

        $this->data['customer_group'] = CustomerGroup::find($order_info['customer_group_id'])->name;

        if ($order_info['invoice_id']) {
            $this->data['invoice_id'] = $order_info['invoice_prefix'].$order_info['invoice_id'];
        } else {
            $this->data['invoice_id'] = '';
        }

        foreach ($this->data['fields'] as $f) {
            if (isset ($this->request->post [$f])) {
                $this->data [$f] = $this->request->post [$f];
            } elseif (isset($order_info[$f])) {
                $this->data[$f] = $order_info[$f];
            } else {
                $this->data[$f] = '';
            }
        }

        $this->data['email'] = $this->html->buildInput([
            'name'  => 'email',
            'value' => $order_info['email'],
        ]);
        $this->data['telephone'] = $this->html->buildInput([
            'name'  => 'telephone',
            'value' => $order_info['telephone'],
        ]);

        $this->data['fax'] = $this->html->buildInput([
            'name'  => 'fax',
            'value' => $order_info['fax'],
        ]);

        if (isset($order_info['im'])) {
            foreach ($order_info['im'] as $protocol => $setting) {
                if ($setting['uri']) {
                    $this->data['im'][$protocol] = $setting['uri'];
                }
            }
        }

        $this->loadModel('catalog/product');
        $this->data['categories'] = Category::getCategories();

        $this->data['order_products'] = [];

        $order_products = OrderProduct::where('order_id', '=', $order_id)->get()->toArray();

        foreach ($order_products as $order_product) {
            $option_data = [];
            $options = OrderProduct::getOrderProductOptions($order_id, $order_product['order_product_id']);
            foreach ($options as $option) {
                $value = $option['value'];
                //generate link to download uploaded files
                if ($option['element_type'] == 'U') {
                    $file_settings = unserialize($option['settings']);
                    $filename = $value;
                    if (H::has_value($file_settings['directory'])) {
                        $file = ABC::env('DIR_APP').'system'.DS.'uploads'.DS.$file_settings['directory'].DS.$filename;
                    } else {
                        $file = ABC::env('DIR_APP').'system'.DS.'uploads'.DS.$filename;
                    }

                    if (is_file($file)) {
                        $value = '<a href="'.$this->html->getSecureURL(
                                'tool/files/download',
                                '&filename='.urlencode($filename).'&order_option_id='.(int)$option['order_option_id']
                            ).'" title=" to download file" target="_blank">'.$value.'</a>';
                    } else {
                        $value = '<span title="file '.$file.' is unavailable">'.$value.'</span>';
                    }

                } elseif ($option['element_type'] == 'C' && $value == 1) {
                    $value = '';
                }
                $title = '';
                // strip long textarea value
                if ($option['element_type'] == 'T') {
                    $title = strip_tags($value);
                    $title = str_replace('\r\n', "\n", $title);

                    $value = str_replace('\r\n', "\n", $value);
                    if (mb_strlen($value) > 64) {
                        $value = mb_substr($value, 0, 64).'...';
                    }
                }

                $option_data[] = [
                    'name'                    => $option['name'],
                    'value'                   => nl2br($value),
                    'title'                   => $title,
                    'product_option_id'       => $option['product_option_id'],
                    'product_option_value_id' => $option['product_option_value_id'],
                ];
            }

            //check if this product product is still available, so we can use recalculation against the cart
            $product = $this->model_catalog_product->getProduct($order_product['product_id']);
            if(!$this->config->get('config_allow_order_recalc')) {
                if (empty($product) || !$product['status'] || $product['call_to_order']) {
                    $this->data['no_recalc_allowed'] = true;
                    $product['status'] = 0;
                } else {
                    if (H::dateISO2Int($product['date_available']) > time()) {
                        $this->data['no_recalc_allowed'] = true;
                        $product['status'] = 0;
                    }
                }
            }

            //get combined database and config info about each order status
            $orderStatuses = OrderStatus::getOrderStatusConfig();
            $this->data['cancel_statuses'] = [];
            foreach ($orderStatuses as $oStatus) {
                if (in_array('return_to_stock', (array)$oStatus['config']['actions'])) {
                    $this->data['cancel_statuses'][] = $oStatus['order_status_id'];
                }
            }
            $orderStatus = OrderStatusDescription::where(
                                [
                                    'order_status_id' => (int)$order_product['order_status_id'],
                                    'language_id'     => (int)$order_info['language_id'],
                                ]
                            )->first();
            $this->data['order_products'][] = [
                'disable_edit'     => in_array($order_product['order_status_id'], $this->data['cancel_statuses']),
                'order_product_id' => $order_product['order_product_id'],
                'product_id'       => $order_product['product_id'],
                'product_status'   => $product['status'],
                'order_status_id'  => $order_product['order_status_id'],
                'order_status'     => ( $orderStatus
                                        ? $orderStatus->name
                                        : Registry::order_status()->getStatusById($order_product['order_status_id'])
                                      ),
                'name'             => $order_product['name'],
                'model'            => $order_product['model'],
                'option'           => $option_data,
                'quantity'         => $order_product['quantity'],
                'price'            => $this->currency->format(
                    $order_product['price'],
                    $order_info['currency'],
                    $order_info['value']
                ),
                'price_value'      => $this->currency->format(
                    $order_product['price'],
                    $order_info['currency'],
                    $order_info['value'],
                    false
                ),
                'total'            => $this->currency->format_total(
                    $order_product['price'],
                    $order_product['quantity'],
                    $order_info['currency'], $order_info['value']
                ),
                'total_value'      => $this->currency->format(
                                            $order_product['price'],
                                            $order_info['currency'],
                                            $order_info['value'],
                                            false
                                        ) * $order_product['quantity'],
                'href'             => $this->html->getSecureURL(
                    'catalog/product/update',
                    '&product_id='.$order_product['product_id']
                ),
            ];
        }

        $this->data['currency'] = $this->currency->getCurrency($order_info['currency']);
        $this->data['totals'] = OrderTotal::where('order_id', '=', $order_id)
                                          ->orderBy('sort_order')
                                          ->get()
                                          ->toArray();

        //check which totals cannot reapply
        foreach ($this->data['totals'] as &$ototal) {
            if ($this->config->get($ototal['key'].'_status')) {
                $ototal['unavailable'] = false;
            } else {
                $ototal['unavailable'] = true;
            }
        }

        $this->data['form_title'] = $this->language->get('edit_title_details');
        $this->data['update'] = $this->html->getSecureURL('listing_grid/order/update_field', '&id='.$order_id);
        $form = new AForm('HS');

        $form->setForm([
            'form_name' => 'orderFrm',
            'update'    => $this->data['update'],
        ]);

        $this->data['form']['id'] = 'orderFrm';
        $this->data['form']['form_open'] = $form->getFieldHtml(
            [
                'type'   => 'form',
                'name'   => 'orderFrm',
                'attr'   => 'data-confirm-exit="true" class="aform form-horizontal"',
                'action' => $this->data['action'],
            ]
        );
        $this->data['form']['submit'] = $form->getFieldHtml(
            [
                'type'  => 'button',
                'name'  => 'submit',
                'text'  => $this->language->get('button_save'),
                'style' => 'button1',
            ]
        );
        $this->data['form']['cancel'] = $form->getFieldHtml(
            [
                'type'  => 'button',
                'name'  => 'cancel',
                'text'  => $this->language->get('button_cancel'),
                'style' => 'button2',
            ]
        );

        //add enabled but not present totals such as discount and fee.
        $this->loadModel('setting/extension');
        $total_ext = $this->extensions->getExtensionsList(['filter' => 'total']);

        //trick for hook
        $allowed_totals = array_merge(['coupon'], (array)$this->data['manual_totals']);

        $manual_total_list = ['' => $this->language->get('text_select')];
        foreach ($total_ext->rows as $ext) {
            if (!in_array($ext['key'], $allowed_totals)) {
                continue;
            }

            if ($this->config->get($ext['key'].'_status')) {
                $manual_total_list[$ext['key']] = $this->extensions->getExtensionName($ext['key']);
            }
        }
        $this->data['manual_totals'] = $form->getFieldHtml(
            [
                'type'    => 'selectbox',
                'name'    => 'manual_total',
                'value'   => $this->data['manually_added_totals'],
                'options' => $manual_total_list,
            ]
        );

        $this->data['manual_coupon_code_field'] = $form->getFieldHtml(
            [
                'type'  => 'input',
                'name'  => 'coupon_code',
                'value' => '',
            ]
        );

        $this->data['validate_coupon_url'] = $this->html->getSecureURL(
            'r/sale/order/validateCoupon',
            '&order_id='.$order_id
        );

        //if virtual product (no shipment);
        if (!$this->data['shipping_method']) {
            $this->data['form']['fields']['shipping_method'] = $this->language->get('text_not_applicable');
        } else {
            $this->data['form']['fields']['shipping_method'] = $this->data['shipping_method'];
        }
        // no payment
        if (!$this->data['payment_method']) {
            $this->data['form']['fields']['payment_method'] = $this->language->get('text_not_applicable');
        } else {
            $this->data['form']['fields']['payment_method'] = $this->data['payment_method'];
        }

        $this->data['add_product'] = $this->html->buildElement([
            'type'          => 'multiselectbox',
            'name'          => 'add_product',
            'value'         => '',
            'options'       => [],
            'style'         => 'aform_noaction chosen',
            'ajax_url'      => $this->html->getSecureURL(
                'r/product/product/products',
                '&currency_code='.$this->data['currency']['code']
            ),
            'placeholder'   => $this->language->get('text_select_from_lookup'),
            'option_attr'   => ['price'],
            'filter_params' => 'enabled_only'
            // list of json-item properties that becomes html5 attributes of option tag.
            // Ex. price will be data-price="00.000"
        ]);

        $this->data['add_product_url'] = $this->html->getSecureURL(
            'r/product/product/orderProductForm',
            '&order_id='.$order_id
            .'&mode=json'
            .'&currency='.$order_info['currency']
        );
        $this->data['recalculate_totals_url'] = $this->html->getSecureURL(
            'r/sale/order/recalculateExistingOrderTotals',
            '&order_id='.$order_id
        );

        $this->data['delete_order_total'] = $this->html->getSecureURL(
            'sale/order/delete_total',
            '&order_id='.$order_id
        );

        $saved_list_data = json_decode(html_entity_decode($this->request->cookie['grid_params']));
        if ($saved_list_data->table_id == 'order_grid') {
            $this->data['list_url'] = $this->html->getSecureURL('sale/order', '&saved_list=order_grid');
        }

        $this->view->batchAssign($this->data);
        $this->view->assign('help_url', $this->gen_help_url('order_details'));

        if ($viewport_mode == 'modal') {
            $tpl = 'responses/viewport/modal/sale/order_details.tpl';
        } else {
            $this->addChild('pages/sale/order_summary', 'summary_form', 'pages/sale/order_summary.tpl');
            $tpl = 'pages/sale/order_details.tpl';
        }

        $this->processTemplate($tpl);

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function shipping()
    {

        $this->data = [
            'fields' => [
                'shipping_firstname',
                'shipping_lastname',
                'shipping_company',
                'shipping_address_1',
                'shipping_address_2',
                'shipping_city',
                'shipping_postcode',
                'fax',
                'shipping_zone',
                'shipping_zone_id',
                'shipping_country',
                'shipping_country_id',
            ],
        ];

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->document->setTitle($this->language->get('heading_title'));

        if (isset($this->request->get['order_id'])) {
            $order_id = (int)$this->request->get['order_id'];
        } else {
            $order_id = 0;
        }
        $order_info = Order::getOrderArray($order_id, 'any');
        $post = $this->request->post;
        $post['order_status_id'] = $order_info['order_status_id'];

        if ($this->request->is_POST() && $this->validateHistoryForm($order_id, $post)) {
            try {
                Order::editOrder($this->request->get['order_id'], $this->request->post);
                $this->session->data['success'] = $this->language->get('text_success');
            } catch (AException $e) {
                $this->session->data['error'] = $e->getMessage();
            }
            $this->extensions->hk_ProcessData($this, __FUNCTION__);
            abc_redirect(
                $this->html->getSecureURL(
                    'sale/order/shipping',
                    '&order_id='.$this->request->get['order_id'])
            );
        }

        if (empty($order_info)) {
            $this->session->data['error'] = $this->language->get('error_order_load');
            abc_redirect($this->html->getSecureURL('sale/order'));
        }

        //set content language to order language ID.
        if ($this->language->getContentLanguageID() != $order_info['language_id']) {
            //reset content language
            $this->language->setCurrentContentLanguage($order_info['language_id']);
        }

        $this->document->initBreadcrumb([
            'href'      => $this->html->getSecureURL('index/home'),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);

        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('sale/order'),
            'text'      => $this->language->get('heading_title'),
            'separator' => ' :: ',
        ]);
        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('sale/order/details', '&order_id='.$order_id),
            'text'      => $this->language->get('heading_title').' #'.$order_id,
            'separator' => ' :: ',
        ]);
        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('sale/order/shipping', '&order_id='.$order_id),
            'text'      => $this->language->get('tab_shipping'),
            'separator' => ' :: ',
            'current'   => true,
        ]);

        if (isset($this->session->data['success'])) {
            $this->data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $this->data['success'] = '';
        }

        $this->data['order_id'] = $order_id;
        $this->data['invoice_url'] = $this->html->getSecureURL('sale/invoice', '&order_id='.$order_id);
        $this->data['button_invoice'] = $this->html->buildButton([
            'name'  => 'invoice',
            'text'  => $this->language->get('text_invoice'),
            'style' => 'button3',
        ]);
        $this->data['action'] = $this->html->getSecureURL('sale/order/shipping', '&order_id='.$order_id);
        $this->data['cancel'] = $this->html->getSecureURL('sale/order');
        $this->data['common_zone'] = $this->html->getSecureURL('common/zone');

        $this->_initTabs('shipping');

        foreach ($this->data['fields'] as $f) {
            if (isset ($this->request->post [$f])) {
                $this->data [$f] = $this->request->post [$f];
            } elseif (isset($order_info[$f])) {
                $this->data[$f] = $order_info[$f];
            }
        }

        $this->data['form_title'] = $this->language->get('edit_title_shipping');
        $this->data['update'] = $this->html->getSecureURL('listing_grid/order/update_field', '&id='.$order_id);
        $form = new AForm('HS');

        $form->setForm([
            'form_name' => 'orderFrm',
            'update'    => $this->data['update'],
        ]);

        $this->data['form']['id'] = 'orderFrm';
        $this->data['form']['form_open'] = $form->getFieldHtml([
            'type'   => 'form',
            'name'   => 'orderFrm',
            'attr'   => 'data-confirm-exit="true" class="aform form-horizontal"',
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

        foreach ($this->data['fields'] as $f) {
            if ($f == 'shipping_zone') {
                break;
            }
            $name = str_replace('shipping_', '', $f);
            $this->data['form']['fields'][$name] = $form->getFieldHtml([
                'type'  => 'input',
                'name'  => $f,
                'value' => $this->data[$f],
            ]);
        }

        $this->data['form']['fields']['fax'] = $form->getFieldHtml([
            'type'  => 'input',
            'name'  => 'fax',
            'value' => $this->data['fax'],
        ]);

        $this->loadModel('localisation/country');
        $this->data['countries'] = $this->model_localisation_country->getCountries();
        $this->data['countries'] = array_merge(
            [
                0 => [
                    'country_id'   => 0,
                    'country_name' => $this->language->get('text_select_country'),
                ],
            ],
            $this->data['countries']
        );

        $countries = [];
        foreach ($this->data['countries'] as $country) {
            $countries[$country['country_id']] = $country['name'];
        }

        if (!$this->data['shipping_country_id']) {
            $this->data['shipping_country_id'] = $this->config->get('config_country_id');
        }

        $this->data['form']['fields']['country'] = $form->getFieldHtml([
            'type'    => 'selectbox',
            'name'    => 'shipping_country_id',
            'value'   => $this->data['shipping_country_id'],
            'options' => $countries,
        ]);

        $this->data['form']['fields']['zone'] = $form->getFieldHtml([
            'type'    => 'selectbox',
            'name'    => 'shipping_zone_id',
            'value'   => '',
            'options' => [],
        ]);

        $this->addChild('pages/sale/order_summary', 'summary_form', 'pages/sale/order_summary.tpl');
        $this->view->assign('help_url', $this->gen_help_url('order_shipping'));
        $this->view->batchAssign($this->data);

        $this->processTemplate('pages/sale/order_shipping.tpl');

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function payment()
    {
        $this->data = [
            'fields' =>
                [
                    'payment_firstname',
                    'payment_lastname',
                    'payment_company',
                    'payment_address_1',
                    'payment_address_2',
                    'payment_city',
                    'payment_postcode',
                    'payment_zone',
                    'payment_zone_id',
                    'payment_country',
                    'payment_country_id',
                ],
        ];

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->document->setTitle($this->language->get('heading_title'));

        if (isset($this->request->get['order_id'])) {
            $order_id = (int)$this->request->get['order_id'];
        } else {
            $order_id = 0;
        }
        $order_info = Order::getOrderArray($order_id, 'any');
        $post = $this->request->post;
        $post['order_status_id'] = $order_info['order_status_id'];

        if ($this->request->is_POST()
            && $this->validateHistoryForm($this->request->get['order_id'], $post)
        ) {
            try {
                Order::editOrder($this->request->get['order_id'], $this->request->post);
                $this->session->data['success'] = $this->language->get('text_success');
            } catch (AException $e) {
                $this->session->data['error'] = $e->getMessage();
            }

            $this->extensions->hk_ProcessData($this, __FUNCTION__);
            abc_redirect(
                $this->html->getSecureURL(
                    'sale/order/payment',
                    '&order_id='.$this->request->get['order_id'])
            );

            $this->session->data['success'] = $this->language->get('text_success');
            abc_redirect($this->html->getSecureURL(
                'sale/order/payment',
                '&order_id='.$this->request->get['order_id'])
            );
        }

        if (empty($order_info)) {
            $this->session->data['error'] = $this->language->get('error_order_load');
            abc_redirect($this->html->getSecureURL('sale/order'));
        }

        //set content language to order language ID.
        if ($this->language->getContentLanguageID() != $order_info['language_id']) {
            //reset content language
            $this->language->setCurrentContentLanguage($order_info['language_id']);
        }

        $this->document->initBreadcrumb([
            'href'      => $this->html->getSecureURL('index/home'),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);

        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('sale/order'),
            'text'      => $this->language->get('heading_title'),
            'separator' => ' :: ',
        ]);
        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('sale/order/details', '&order_id='.$order_id),
            'text'      => $this->language->get('heading_title').' #'.$order_id,
            'separator' => ' :: ',
        ]);
        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('sale/order/payment', '&order_id='.$order_id),
            'text'      => $this->language->get('tab_payment'),
            'separator' => ' :: ',
            'current'   => true,
        ]);

        if (isset($this->session->data['success'])) {
            $this->data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $this->data['success'] = '';
        }

        $this->data['order_id'] = $order_id;
        $this->data['invoice_url'] = $this->html->getSecureURL('sale/invoice', '&order_id='.$order_id);
        $this->data['button_invoice'] = $this->html->buildButton(
            [
                'name'  => 'invoice',
                'text'  => $this->language->get('text_invoice'),
                'style' => 'button3',
            ]
        );

        $this->data['action'] = $this->html->getSecureURL('sale/order/payment', '&order_id='.$order_id);
        $this->data['cancel'] = $this->html->getSecureURL('sale/order');
        $this->data['common_zone'] = $this->html->getSecureURL('common/zone');

        $this->_initTabs('payment');

        foreach ($this->data['fields'] as $f) {
            if (isset ($this->request->post [$f])) {
                $this->data [$f] = $this->request->post [$f];
            } elseif (isset($order_info[$f])) {
                $this->data[$f] = $order_info[$f];
            }
        }

        $this->data['form_title'] = $this->language->get('edit_title_payment');
        $this->data['update'] = $this->html->getSecureURL('listing_grid/order/update_field', '&id='.$order_id);
        $form = new AForm('HS');

        $form->setForm([
            'form_name' => 'orderFrm',
            'update'    => $this->data['update'],
        ]);

        $this->data['form']['id'] = 'orderFrm';
        $this->data['form']['form_open'] = $form->getFieldHtml(
            [
                'type'   => 'form',
                'name'   => 'orderFrm',
                'attr'   => 'data-confirm-exit="true" class="aform form-horizontal"',
                'action' => $this->data['action'],
            ]
        );
        $this->data['form']['submit'] = $form->getFieldHtml(
            [
                'type'  => 'button',
                'name'  => 'submit',
                'text'  => $this->language->get('button_save'),
                'style' => 'button1',
            ]
        );
        $this->data['form']['cancel'] = $form->getFieldHtml([
            'type'  => 'button',
            'name'  => 'cancel',
            'text'  => $this->language->get('button_cancel'),
            'style' => 'button2',
        ]);

        foreach ($this->data['fields'] as $f) {
            if ($f == 'payment_zone') {
                break;
            }
            $name = str_replace('payment_', '', $f);
            $this->data['form']['fields'][$name] = $form->getFieldHtml([
                'type'  => 'input',
                'name'  => $f,
                'value' => $this->data[$f],
            ]);
        }

        $this->loadModel('localisation/country');
        $this->data['countries'] = $this->model_localisation_country->getCountries();

        $countries = [];
        foreach ($this->data['countries'] as $country) {
            $countries[$country['country_id']] = $country['name'];
        }

        if (!$this->data['payment_country_id']) {
            $this->data['payment_country_id'] = $this->config->get('config_country_id');
        }

        $this->data['form']['fields']['country'] = $form->getFieldHtml(
            [
                'type'    => 'selectbox',
                'name'    => 'payment_country_id',
                'value'   => $this->data['payment_country_id'],
                'options' => $countries,
                'style'   => 'no-save',
            ]
        );

        $this->data['form']['fields']['zone'] = $form->getFieldHtml(
            [
                'type'    => 'selectbox',
                'name'    => 'payment_zone_id',
                'value'   => '',
                'options' => [],
                'style'   => 'no-save',
            ]
        );

        $this->addChild('pages/sale/order_summary', 'summary_form', 'pages/sale/order_summary.tpl');

        $this->view->assign('help_url', $this->gen_help_url('order_payment'));
        $this->view->batchAssign($this->data);

        $this->processTemplate('pages/sale/order_payment.tpl');

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function history()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->data = [];
        $this->document->setTitle($this->language->get('heading_title'));

        if (isset($this->request->get['order_id'])) {
            $order_id = (int)$this->request->get['order_id'];
        } else {
            $order_id = 0;
        }
        $order_info = Order::getOrderArray($order_id, 'any');
        $post = $this->request->post;
        $post['order_status_id'] = $order_info['order_status_id'];

        if ($this->request->is_POST()
            && $this->validateHistoryForm($order_id, $post)
        ) {
            $post = $this->request->post;
            $this->db->beginTransaction();
            try {
                $data = [
                    'order_id'        => $this->request->get['order_id'],
                    'order_status_id' => $post['order_status_id'],
                    'notify'          => ($post['notify'] ? true : false),
                    'comment'         => $post['comment'],
                ];
                $oHistory = new OrderHistory($data);
                $oHistory->save();
                $this->db->commit();
                $this->session->data['success'] = $this->language->get('text_success');
                H::event('admin\SendOrderStatusNotifyEmail', [new ABaseEvent($data)]);
            } catch (\Exception $e) {
                $this->session->data['error'] = H::getAppErrorText();
                $this->db->rollback();
            }

            abc_redirect(
                $this->html->getSecureURL(
                    'sale/order/history',
                    '&order_id='.$this->request->get['order_id']
                )
            );
        }

        if (empty($order_info)) {
            $this->session->data['error'] = $this->language->get('error_order_load');
            abc_redirect($this->html->getSecureURL('sale/order'));
        }
        if ($this->error) {
            $this->session->data['error'] = implode('<br>', $this->error);
            abc_redirect($this->html->getSecureURL('sale/order/history', '&order_id='.$order_id));
        }

        //set content language to order language ID.
        if ($this->language->getContentLanguageID() != $order_info['language_id']) {
            //reset content language
            $this->language->setCurrentContentLanguage($order_info['language_id']);
        }

        $this->document->initBreadcrumb([
            'href'      => $this->html->getSecureURL('index/home'),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);

        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('sale/order'),
            'text'      => $this->language->get('heading_title'),
            'separator' => ' :: ',
        ]);
        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('sale/order/details', '&order_id='.$order_id),
            'text'      => $this->language->get('heading_title').' #'.$order_id,
            'separator' => ' :: ',
        ]);
        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('sale/order/history', '&order_id='.$order_id),
            'text'      => $this->language->get('tab_history'),
            'separator' => ' :: ',
            'current'   => true,
        ]);

        if (isset($this->session->data['success'])) {
            $this->data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $this->data['success'] = '';
        }

        $results = OrderStatus::with('description')->get()->toArray();
        $statuses = ['' => $this->language->get('text_select_status'),];
        $disabled_statuses = [];
        foreach ($results as $item) {
            if ($item['display_status'] || $order_info['order_status_id'] == $item['order_status_id']) {
                $statuses[$item['order_status_id']] = $item['description']['name'];
            }
            if (!$item['display_status']) {
                $disabled_statuses[] = (string)$item['order_status_id'];
            }
        }

        $this->data['order_id'] = $order_id;
        $this->data['invoice_url'] = $this->html->getSecureURL('sale/invoice', '&order_id='.$order_id);
        $this->data['button_invoice'] = $this->html->buildButton([
            'name'  => 'invoice',
            'text'  => $this->language->get('text_invoice'),
            'style' => 'button3',
        ]);
        $this->data['order_history'] = $this->html->getSecureURL('sale/order_history');
        $this->data['cancel'] = $this->html->getSecureURL('sale/order');

        $this->_initTabs('history');

        $this->data['action'] = $this->html->getSecureURL('sale/order/history', '&order_id='.$order_id);
        $this->data['form_title'] = $this->language->get('text_edit').' '.$this->language->get('tab_history');
        $form = new AForm('ST');

        $form->setForm([
            'form_name' => 'orderFrm',
            'update'    => $this->data['update'],
        ]);

        $this->data['form']['id'] = 'orderFrm';
        $this->data['form']['form_open'] = $form->getFieldHtml([
            'type'   => 'form',
            'name'   => 'orderFrm',
            'attr'   => 'data-confirm-exit="true" class="aform form-horizontal"',
            'action' => $this->data['action'],
        ]);
        $this->data['form']['submit'] = $form->getFieldHtml([
            'type'  => 'button',
            'name'  => 'submit',
            'text'  => $this->language->get('button_add_history'),
            'style' => 'button1',
        ]);
        $this->data['form']['cancel'] = $form->getFieldHtml([
            'type'  => 'button',
            'name'  => 'cancel',
            'text'  => $this->language->get('button_cancel'),
            'style' => 'button2',
        ]);

        if(in_array($this->order_status->getStatusById($order_info['order_status_id']), (array)ABC::env('ORDER')['not_reversal_statuses']) ){
            $attr = 'readonly';
        }else{
            $attr = '';
        }
        $this->data['form']['fields']['order_status'] = $form->getFieldHtml([
            'type'             => 'selectbox',
            'name'             => 'order_status_id',
            'value'            => $order_info['order_status_id'],
            'options'          => $statuses,
            'disabled_options' => $disabled_statuses,
            'attr'             => $attr
        ]);

        $this->data['form']['fields']['notify'] = $form->getFieldHtml([
            'type'    => 'checkbox',
            'name'    => 'notify',
            'value'   => 1,
            'checked' => false,
            'style'   => 'btn_switch',
        ]);

        $this->data['form']['fields']['append'] = $form->getFieldHtml([
            'type'  => 'checkbox',
            'name'  => 'append',
            'value' => 1,
            'style' => 'btn_switch',
        ]);
        $this->data['form']['fields']['comment'] = $form->getFieldHtml([
            'type'  => 'textarea',
            'name'  => 'comment',
            'style' => 'large-field',
        ]);

        $this->data['histories'] = [];
        $results = OrderHistory::with('order_status_description')
                               ->where('order_id', '=', $this->request->get['order_id'])
                               ->orderBy('date_added')
                               ->get()
                               ->toArray();
        foreach ($results as $result) {
            $this->data['histories'][] = [
                'date_added' => H::dateISO2Display(
                    $result['date_added'],
                    $this->language->get('date_format_short').' '.$this->language->get('time_format')
                ),
                'status'     => $result['order_status_description']['name'],
                'comment'    => nl2br($result['comment']),
                'notify'     => $result['notify'] ? $this->language->get('text_yes') : $this->language->get('text_no'),
            ];
        }

        if( $this->session->data['error'] ){
            $this->data['error_warning'] = $this->session->data['error'];
            unset($this->session->data['error']);
        }

        $this->addChild('pages/sale/order_summary', 'summary_form', 'pages/sale/order_summary.tpl');

        $this->view->assign('help_url', $this->gen_help_url('order_history'));
        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/sale/order_history.tpl');

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function payment_details()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->loadLanguage('sale/order');

        $this->data = [];

        $this->document->setTitle($this->language->get('title_payment_details'));

        $order_id = (int)$this->request->get['order_id'];
        $this->data['order_id'] = $order_id;

        $order_info = Order::getOrderArray($order_id, 'any');
        $this->data['order_info'] = $order_info;

        if (empty($order_info)) {
            $this->session->data['error'] = $this->language->get('error_order_load');
            abc_redirect($this->html->getSecureURL('sale/order'));
        }

        //set content language to order language ID.
        if ($this->language->getContentLanguageID() != $order_info['language_id']) {
            //reset content language
            $this->language->setCurrentContentLanguage($order_info['language_id']);
        }

        $this->document->initBreadcrumb([
            'href'      => $this->html->getSecureURL('index/home'),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);

        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('sale/order'),
            'text'      => $this->language->get('heading_title'),
            'separator' => ' :: ',
        ]);
        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('sale/order/payment_details', '&order_id='.$order_id),
            'text'      => $this->language->get('title_payment_details').' #'.$order_info['order_id'],
            'separator' => ' :: ',
            'current'   => true,
        ]);

        $this->data['invoice_url'] = $this->html->getSecureURL('sale/invoice', '&order_id='.$order_id);
        $this->_initTabs('payment_details');

        //NOTE: This is an empty controller to be hooked from extensions

        if( $this->session->data['error'] ){
            $this->data['error_warning'] = $this->session->data['error'];
            unset($this->session->data['error']);
        }

        $this->view->batchAssign($this->data);

        $this->addChild('pages/sale/order_summary', 'summary_form', 'pages/sale/order_summary.tpl');
        $this->view->assign('help_url', $this->gen_help_url('order_history'));
        $this->processTemplate('pages/sale/order_payment_details.tpl');

        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    protected function validateDetailsForm($order_id, $data)
    {
        if (!$this->user->canModify('sale/order')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        //check is order exists
        $order = Order::find($order_id);
        if(!$order){
            $this->error['not_found'] = 'Order #'.$order_id.' not found!';
        }

        if($data['product'] && !is_array($data['product'])){

            $this->error['products_list'] = 'Products List of Order #'.$order_id.' must be an array!';
        }

        if($data['product']){
            foreach($data['product'] as $item){
                $product = Product::find($item['product_id']);
                //when product already deleted from database
                if(!$product){
                    $orderProduct = OrderProduct::find($item['order_product_id']);
                    if(!$orderProduct){
                        $this->error['order_product_not_found'] = 'Order Product #'.$item['order_product_id'].' not found!';
                        break;
                    }
                    $prev_quantity = $orderProduct->quantity;
                    if($prev_quantity != $item['quantity'] && $item['quantity'] != 0){
                        $this->error['product_error'] = 'Product #'.$item['product_id'].' already deleted! You cannot to change it\'s quantity in order!';
                        break;
                    }
                }elseif( (int)$item['order_product_id']>0 ){
                    //remove options from post data for existing order product.
                    //do not allow to change options!
                    unset($this->request->post['product'][$item['order_product_id']]['option']);

                }
            }
        }

        if($data['order_totals'] && !is_array($data['order_totals'])){
            $this->error['totals_list'] = 'Totals List of Order #'.$order_id.' must be an array!';
        }

        $this->extensions->hk_ValidateData($this, $data);

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }

    protected function validateHistoryForm($order_id, $data)
    {
        if (!$this->user->canModify('sale/order')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        $data['order_id'] = $order_id;
        $oHistory = new OrderHistory();
        try {
            $oHistory->validate($data);
        } catch (ValidationException $e) {
            H::SimplifyValidationErrors($oHistory->errors()['validation'], $this->error);
        }

        if(in_array($this->order_status->getStatusById($data['order_status_id']), (array)ABC::env('ORDER')['not_reversal_statuses']) ){
            $this->error['not_reversal_status'] = 'This Order status is not reversal!';
        }

        $this->extensions->hk_ValidateData($this, $data);

        if ($this->error) {
            Registry::log()->write(var_export($this->error, true));
        }

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }

    protected function validateDownloadsForm()
    {
        if (!$this->user->canModify('sale/order')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        $this->extensions->hk_ValidateData($this);

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }

    protected function _initTabs($active)
    {
        $this->data['active'] = $active;
        //load tabs controller
        $tabs_obj = $this->dispatch('pages/sale/order_tabs', [$this->data]);
        $this->data['order_tabs'] = $tabs_obj->dispatchGetOutput();
    }

    public function files()
    {

        $this->data = [];
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->document->setTitle($this->language->get('heading_title'));

        if (H::has_value($this->session->data['error'])) {
            $this->data['error']['warning'] = $this->session->data['error'];
            unset($this->session->data['error']);
        }

        if (isset($this->request->get['order_id'])) {
            $order_id = $this->request->get['order_id'];
        } else {
            $order_id = 0;
        }

        if ($this->request->is_POST() && $this->validateDownloadsForm()) {
            if (H::has_value($this->request->post['downloads'])) {
                $data = $this->request->post['downloads'];
                $this->loadModel('catalog/download');
                foreach ($data as $order_download_id => $item) {
                    if (isset($item['expire_date'])) {
                        $item['expire_date'] =
                            $item['expire_date'] ? H::dateDisplay2ISO($item['expire_date'],
                                $this->language->get('date_format_short')) : '';
                    }
                    $this->model_catalog_download->editOrderDownload($order_download_id, $item);
                }
            }
            //add download to order
            if (H::has_value($this->request->post['push'])) {
                $this->load->library('json');
                foreach ($this->request->post['push'] as $order_product_id => $download_id) {
                    if ($download_id) {
                        $download_info = $this->download->getDownloadInfo($download_id);
                        $download_info['attributes_data'] = serialize(
                            $this->download->getDownloadAttributesValues($download_id)
                        );
                        $this->download->addProductDownloadToOrder($order_product_id, $order_id, $download_info);
                    }
                }
            }

            $this->session->data['success'] = $this->language->get('text_success');
            abc_redirect($this->html->getSecureURL(
                'sale/order/files',
                '&order_id='.$this->request->get['order_id'])
            );
        }

        $order_info = Order::getOrderArray($order_id, 'any');
        $this->data['order_info'] = $order_info;

        //set content language to order language ID.
        if ($this->language->getContentLanguageID() != $order_info['language_id']) {
            //reset content language
            $this->language->setCurrentContentLanguage($order_info['language_id']);
        }

        if (empty($order_info)) {
            $this->session->data['error'] = $this->language->get('error_order_load');
            abc_redirect($this->html->getSecureURL('sale/order'));
        }

        $this->document->initBreadcrumb([
            'href'      => $this->html->getSecureURL('index/home'),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);

        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('sale/order'),
            'text'      => $this->language->get('heading_title'),
            'separator' => ' :: ',
        ]);

        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSecureURL(
                    'sale/order/files',
                    '&order_id='.$this->request->get['order_id']
                ),
                'text'      => $this->language->get('heading_title').' #'.$order_info['order_id'],
                'separator' => ' :: ',
                'current'   => true,
            ]
        );

        if (isset($this->session->data['success'])) {
            $this->data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $this->data['success'] = '';
        }

        $this->data['heading_title'] = $this->language->get('heading_title').' #'.$order_info['order_id'];
        $this->data['token'] = $this->session->data['token'];
        $this->data['invoice_url'] = $this->html->getSecureURL('sale/invoice',
            '&order_id='.(int)$this->request->get['order_id']);
        $this->data['button_invoice'] = $this->html->buildButton(
            [
                'name' => 'btn_invoice',
                'text' => $this->language->get('text_invoice'),
            ]
        );
        $this->data['invoice_generate'] = $this->html->getSecureURL('sale/invoice/generate');
        $this->data['category_products'] = $this->html->getSecureURL('product/product/category');
        $this->data['product_update'] = $this->html->getSecureURL('catalog/product/update');
        $this->data['order_id'] = $this->request->get['order_id'];
        $this->data['action'] = $this->html->getSecureURL(
            'sale/order/files',
            '&order_id='.$this->request->get['order_id']
        );

        $this->data['cancel'] = $this->html->getSecureURL('sale/order');

        $this->_initTabs('files');

        $status = OrderStatus::with('description')->find($order_info['order_status_id']);
        if ($status) {
            $this->data['order_status'] = $status['description']['name'];
        } else {
            $this->data['order_status'] = '';
        }

        $this->loadModel('sale/customer_group');
        $customer_group_info = $this->model_sale_customer_group->getCustomerGroup($order_info['customer_group_id']);
        if ($customer_group_info) {
            $this->data['customer_group'] = $customer_group_info['name'];
        } else {
            $this->data['customer_group'] = '';
        }

        $this->data['form_title'] = $this->language->get('edit_title_files');
        $this->data['update'] = $this->html->getSecureURL(
            'listing_grid/order/update_field',
            '&id='.$this->request->get['order_id']
        );
        $form = new AForm('HS');
        $form->setForm([
            'form_name' => 'orderFrm',
            'update'    => $this->data['update'],
        ]);

        $this->data['form']['id'] = 'orderFrm';
        $this->data['form']['form_open'] = $form->getFieldHtml([
            'type'   => 'form',
            'name'   => 'orderFrm',
            'attr'   => 'data-confirm-exit="true" class="aform form-horizontal"',
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

        $this->loadModel('catalog/download');
        $all_downloads = $this->model_catalog_download->getDownloads();

        $options = ['' => $this->language->get('text_push_download')];
        foreach ($all_downloads as $d) {
            $options[$d['download_id']] = $d['name'].' ('.$d['mask'].')';
        }

        $this->addChild('pages/sale/order_summary', 'summary_form', 'pages/sale/order_summary.tpl');

        /** ORDER DOWNLOADS */
        $this->data['downloads'] = [];
        $order_downloads = OrderDownload::getOrderDownloads($this->request->get['order_id']);

        if ($order_downloads) {
            //get thumbnails by one pass
            $resource = new AResource('image');
            $thumbnails = $resource->getMainThumbList(
                'products',
                array_keys($order_downloads),
                $this->config->get('config_image_grid_width'),
                $this->config->get('config_image_grid_height')
            );

            $this->loadModel('catalog/download');
            foreach ($order_downloads as $product_id => $order_download) {
                $downloads = (array)$order_download['downloads'];
                $this->data['order_downloads'][$product_id]['product_name'] = $order_download['product_name'];
                $this->data['order_downloads'][$product_id]['product_thumbnail'] = $thumbnails[$product_id];

                foreach ($downloads as $download_info) {
                    $download_info['order_status_id'] = $order_info['order_status_id'];
                    $attributes = $this->download->getDownloadAttributesValuesForDisplay($download_info['download_id']);

                    $is_file = $this->download->isFileAvailable($download_info['filename']);
                    foreach ($download_info['download_history'] as &$h) {
                        $h['time'] = H::dateISO2Display(
                            $h['date_added'],
                            $this->language->get('date_format_short').' '.$this->language->get('time_format')
                        );
                    }
                    unset($h);

                    $status_text = $this->model_catalog_download->getTextStatusForOrderDownload($download_info);

                    if ($status_text) {
                        $status = $status_text;
                    } else {
                        $status = $form->getFieldHtml([
                            'type'  => 'checkbox',
                            'name'  => 'downloads['.(int)$download_info['order_download_id'].'][status]',
                            'value' => $download_info['status'],
                            'style' => 'btn_switch',
                        ]);
                    }

                    $this->data['order_downloads'][$product_id]['downloads'][] = [
                        'name'             => $download_info['name'],
                        'attributes'       => $attributes,
                        'href'             => $this->html->getSecureURL(
                            'catalog/product_files',
                            '&product_id='.$product_id.'&download_id='.$download_info['download_id']
                        ),
                        'resource'         => $download_info['filename'],
                        'is_file'          => $is_file,
                        'mask'             => $download_info['mask'],
                        'status'           => $status,
                        'remaining'        => $form->getFieldHtml(
                            [
                                'type'        => 'input',
                                'name'        => 'downloads['.(int)$download_info['order_download_id']
                                    .'][remaining_count]',
                                'value'       => $download_info['remaining_count'],
                                'placeholder' => '-',
                                'style'       => 'small-field',
                            ]
                        ),
                        'expire_date'      => $form->getFieldHtml(
                            [
                                'type'       => 'date',
                                'name'       => 'downloads['.(int)$download_info['order_download_id'].'][expire_date]',
                                'value'      => ($download_info['expire_date'] ? H::dateISO2Display($download_info['expire_date']) : ''),
                                'default'    => '',
                                'dateformat' => H::format4Datepicker($this->language->get('date_format_short')),
                                'highlight'  => 'future',
                                'style'      => 'medium-field',
                            ]),
                        'download_history' => $download_info['download_history'],
                    ];
                    $this->data['order_downloads'][$product_id]['push_download'] = $form->getFieldHtml([
                        'type'        => 'selectbox',
                        'name'        => 'push['.(int)$download_info['order_download_id'].']',
                        'value'       => '',
                        'options'     => $options,
                        'style'       => 'chosen no-save',
                        'placeholder' => $this->language->get('text_push_download'),
                    ]);
                }
            }
        } else {
            abc_redirect($this->html->getSecureURL(
                'sale/order/details',
                '&order_id='.$this->request->get['order_id'])
            );
        }

        $this->view->batchAssign($this->data);
        $this->view->assign('help_url', $this->gen_help_url('order_files'));

        $this->processTemplate('pages/sale/order_files.tpl');

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function createOrder()
    {
        $this->loadLanguage('sale/customer');
        $this->loadLanguage('sale/order');
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $customer_id = (int)$this->request->get['customer_id'];

        if (!$this->session->data['admin_order']
            //check customer id in the session data too! What if switched to another customer?
            || ($customer_id && $this->session->data['admin_order']['customer_id'] != $customer_id)) {
            $this->session->data['admin_order'] = [];
            $this->session->data['admin_order']['cart'] = [];
        }

        $order_info =& $this->session->data['admin_order'];

        if (!$customer_id) {
            if ($order_info['customer_id']) {
                $customer_id = (int)$order_info['customer_id'];
            } else {
                abc_redirect($this->html->getSecureURL('sale/customer'));
            }
        }

        $order_info['customer_id'] = $customer_id;

        $this->document->setTitle($this->language->get('text_create_order'));
        $this->document->initBreadcrumb([
            'href'      => $this->html->getSecureURL('index/home'),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);

        $currency = new ACurrency($this->registry);
        if ($order_info['currency']) {
            $currency->set($order_info['currency']);
        }

        $checkout = $this->initCheckout($order_info);
        $this->data['checkout'] = $checkout;

        $this->preValidateOrder($customer_id);

        if ($this->request->is_POST() && $checkout->getCart()->hasProducts()) {
            $post = $this->request->post;
            $this->db->beginTransaction();
            try {
                $shippings = $checkout->getShippingList();

                if ($shippings && $post['shipping_method']) {
                    list($shp_name, $shp_quote) = explode('.', $post['shipping_method']);
                    $checkout->setShippingMethod($shippings[$shp_name]['quote'][$shp_quote]);
                    $this->session->data['admin_order']['shipping_method'] = $shippings[$shp_name]['quote'][$shp_quote];
                    $this->session->data['admin_order']['shipping_address_id'] = $post['shipping_address_id'];
                }
                $payments = $checkout->getPaymentList();
                $checkout->setPaymentMethod($payments[$post['payment_method']]);
                $this->session->data['admin_order']['payment_method'] =
                    $payments[$post['payment_method']];
                $this->session->data['admin_order']['payment_address_id'] = $post['payment_address_id'];

                $checkout->getOrder()->buildOrderData($this->session->data['admin_order']);
                $order_id = $checkout->getOrder()->saveOrder();
                $this->data['order_id'] = $order_id;

                if (!$order_id) {
                    throw new LibException(['cannot to save newly created order']);
                }
                $checkout->setOrderId((int)$order_id);

                $this->extensions->hk_ProcessData($this, 'before_confirm_order');

                $checkout->confirmOrder(['order_id' => $order_id]);
                $this->extensions->hk_ProcessData($this, 'after_confirm_order');

                unset($this->session->data['admin_order']);
                $this->db->commit();
                abc_redirect($this->html->getSecureURL('sale/order/details', '&order_id='.$order_id));
            } catch (LibException $e) {
                $this->db->rollback();
                $error_text = $e->getMessages();
                if (!$error_text) {
                    $error_text = 'App Error. See error log for details';
                    $this->log->write($e->getTraceAsString());
                }
                $this->data['error_warning'] = $error_text;
            }catch(\Exception $e){
                $this->db->rollback();
                $error_text = 'App Error. '.$e->getMessage();
                $this->log->write($e->getTraceAsString());
                $this->data['error_warning'] = $error_text;
            }
        }

        $flName = $checkout->getCustomer()->getFirstName().' '.$checkout->getCustomer()->getLastName();
        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSecureURL('sale/customers/update', '&customer_id='.$customer_id),
                'text'      => $flName,
                'separator' => ' :: ',
            ]
        );

        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('sale/order/createOrder'),
            'text'      => $this->language->get('text_create_order'),
            'separator' => ' :: ',
            'current'   => true,
        ]);

        $this->data['form_title'] = $this->language->get('text_create_order').' - '.$flName;

        $form = new AForm('HS');
        $this->data['form']['form_open'] = $form->getFieldHtml(
            [
                'type'   => 'form',
                'name'   => 'orderFrm',
                'attr'   => 'data-confirm-exit="true" class="aform form-horizontal"',
                'action' => $this->html->getSecureURL('sale/order/createOrder'),
            ]
        );

        $this->data['list_url'] = $this->html->getSecureURL('sale/customer');

        $balance = CustomerTransaction::getBalance($customer_id);
        $curr = $this->currency->getCurrency($this->config->get('config_currency'));

        $this->data['balance'] = $this->language->get('text_balance')
            .' '.$curr['symbol_left']
            .round($balance, 2)
            .$curr['symbol_right'];

        $this->data['actas'] = $this->html->buildElement([
            'type'   => 'button',
            'text'   => $this->language->get('button_actas'),
            'href'   => $this->html->getSecureURL('sale/customer/actonbehalf', '&customer_id='.$customer_id),
            'target' => 'new',
        ]);

        $customer_info = Customer::find($customer_id);
        if($customer_info){
            $customer_info['orders_count'] = Order::where('customer_id', '=', $customer_id)->where('order_status_id', '>',0)->get()->count();
        }
        $this->data['button_orders_count'] = $this->html->buildElement(
            [
                'type'  => 'button',
                'name'  => 'view orders',
                'text'  => $this->language->get('text_total_order').' '.$customer_info['orders_count'],
                'style' => 'button2',
                'href'  => $this->html->getSecureURL('sale/order', '&customer_id='.$customer_id),
                'title' => $this->language->get('text_view').' '.$this->language->get('tab_history'),
            ]
        );

        if ($customer_info['last_login']
            && !in_array($customer_info['last_login'], ['0000-00-00 00:00:00', '1970-01-01 00:00:00', ''])
        ) {
            $date = H::dateISO2Display(
                $customer_info['last_login'],
                $this->language->get('date_format_short').' '.$this->language->get('time_format')
            );
        } else {
            $date = $this->language->get('text_never');
        }

        $this->data['last_login'] = $this->language->get('text_last_login').' '.$date;
        $this->data['message'] = $this->html->buildElement(
            [
                'type'   => 'button',
                'text'   => $this->language->get('button_message'),
                'href'   => $this->html->getSecureURL('sale/contact', '&to[]='.$customer_id),
                'target' => 'new',
            ]
        );

        //get languages
        if (isset($this->request->get['language_id'])) {
            $order_info['language_id'] = (int)$this->request->get['language_id'];
        }

        $all_languages = $this->language->getAvailableLanguages();
        if (sizeof($all_languages) == 1 || !$order_info['language_id']) {
            $order_info['language_id'] = $this->language->getLanguageID();
        }

        $this->data['order_language_id'] = $this->html->buildElement(
            [
                'type'    => 'selectbox',
                'name'    => 'language_id',
                'options' => array_column($all_languages, 'name', 'language_id'),
                'value'   => $order_info['language_id'],
            ]
        );

        $this->data['transactions_url'] =
            $this->html->getSecureURL('sale/customer_transaction', '&customer_id='.$customer_id);
        //get currencies

        if (isset($this->request->get['order_currency'])) {
            $order_info['currency'] = $this->request->get['order_currency'];
        }

        $all_currencies = Currency::all()->toArray();
        if (sizeof($all_currencies) == 1 || !$order_info['currency']) {
            $order_info['currency'] = $this->config->get('config_currency');
        }
        $this->data['order_currency'] = $this->html->buildElement(
            [
                'type'    => 'selectbox',
                'name'    => 'order_currency',
                'options' => array_column($all_currencies, 'title', 'code'),
                'value'   => $order_info['currency'],
            ]
        );

        // get products list
        $cart_products = $checkout->getCart()->getProducts();
        $product_ids = [];
        foreach ($cart_products as $result) {
            $product_ids[] = (int)$result['product_id'];
        }

        $resource = new AResource('image');
        $thumbnails = $resource->getMainThumbList(
            'products',
            $product_ids,
            $this->config->get('config_image_cart_width'),
            $this->config->get('config_image_cart_height')
        );

        $products = [];
        foreach ($cart_products as $result) {
            $option_data = [];
            $thumbnail = $thumbnails[$result['product_id']];
            foreach ($result['option'] as $option) {
                $title = '';
                //hide hidden options
                if ($option['element_type'] == 'H') {
                    continue;
                }
                $value = $option['value'];
                // hide binary value for checkbox
                if ($option['element_type'] == 'C' && in_array($value, [0, 1])) {
                    $value = '';
                }

                // strip long textarea value
                if ($option['element_type'] == 'T') {
                    $title = strip_tags($value);
                    $title = str_replace('\r\n', "\n", $title);

                    $value = str_replace('\r\n', "\n", $value);
                    if (mb_strlen($value) > 64) {
                        $value = mb_substr($value, 0, 64).'...';
                    }
                }

                $option_data[] = [
                    'name'  => $option['name'],
                    'value' => $value,
                    'title' => $title,
                ];
            }

            $price_with_tax = $checkout->getTax()->calculate(
                $result['price'],
                $result['tax_class_id'],
                $this->config->get('config_tax')
            );

            $products[] = [
                'key'            => $result['key'],
                'product_id'     => $result['product_id'],
                'product_status' => $result['status'],
                'name'           => $result['name'],
                'model'          => $result['model'],
                'thumb'          => $thumbnail,
                'option'         => $option_data,
                'quantity'       => $result['quantity'],
                'price'          => $currency->format($price_with_tax),
                'total'          => $currency->format_total($price_with_tax, $result['quantity']),
                'href'           => $this->html->getSecureURL(
                    'catalog/product/update',
                    '&product_id='.$result['product_id']
                ),
                'remove_url'     => $this->html->getSecureURL(
                    'sale/order/removeProduct',
                    '&product_key='.$result['key']
                ),
            ];

        }

        $this->data['order_products'] = $products;
        $this->data['add_product'] = $this->html->buildElement(
            [
                'type'        => 'multiselectbox',
                'name'        => 'add_product',
                'value'       => '',
                'options'     => [],
                'style'       => 'aform_noaction chosen',
                'ajax_url'    => $this->html->getSecureURL(
                    'r/product/product/products',
                    '&currency_code='.$currency->getCode()
                ),
                'placeholder' => $this->language->get('text_select_from_lookup'),
                'option_attr' => ['price'],
            ]
        );

        $this->data['add_product_url'] = $this->html->getSecureURL(
            'r/product/product/orderProductForm',
            '&callback_rt=sale/order/addProduct'
        );

        //payment address

        $all_addresses = Address::getAddressesByCustomerId($customer_id);
        $default_address_id = $checkout->getCustomer()->getAddressId();

        if (!$order_info['payment_address_id']) {
            $order_info['payment_address_id'] = $default_address_id;
            $checkout->setPaymentAddress($default_address_id);
        }
        if (!$order_info['shipping_address_id']) {
            $order_info['shipping_address_id'] = $default_address_id;
            $checkout->setShippingAddress($default_address_id);
        }
        $addresses = [];
        foreach ($all_addresses as $a) {
            $addresses[$a['address_id']] = $a['address_1'].' '.$a['address_2'];
        }

        $this->data['shipping_address'] = $this->html->buildElement(
            [
                'type'    => 'selectbox',
                'name'    => 'shipping_address_id',
                'options' => $addresses,
                'value'   => $order_info['shipping_address_id'],
            ]
        );
        $this->data['entry_shipping_address'] = $this->language->get('tab_shipping');

        $this->data['payment_address'] = $this->html->buildElement(
            [
                'type'    => 'selectbox',
                'name'    => 'payment_address_id',
                'options' => $addresses,
                'value'   => $order_info['payment_address_id'],
            ]
        );
        $this->data['entry_payment_address'] = $this->language->get('tab_payment');

        $this->data['coupon_code'] = $this->html->buildElement(
            [
                'type'  => 'input',
                'name'  => 'coupon_code',
                'value' => '',
            ]
        );
        $this->data['apply_coupon_button'] = $this->html->buildElement(
            [
                'type'  => 'button',
                'name'  => 'apply_coupon_button',
                'text'  => $this->language->get('text_apply_coupon'),
                //set button not to submit a form
                'attr'  => 'type="button"',
                'style' => 'btn btn-info',
            ]
        );

        $this->data['cancel'] =  $this->html->getSecureURL('sale/customer');

        $this->data['recalc_totals_url'] = $this->html->getSecureURL('r/sale/order', '&action=recalc_totals');
        $this->data['get_shippings_url'] = $this->html->getSecureURL('r/sale/order', '&action=get_shippings');
        $this->data['get_payments_url'] = $this->html->getSecureURL('r/sale/order', '&action=get_payments');
        $this->data['apply_coupon_url'] = $this->html->getSecureURL('r/sale/order', '&action=apply_coupon');

        $this->view->batchAssign($this->data);

        $this->processTemplate('pages/sale/createOrder.tpl');
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    protected function preValidateOrder($customer_id)
    {
        if (!$this->data['checkout']->getCustomer()->getAddressId()) {
            $this->data['error_warning'] = sprintf(
                $this->language->get('error_no_address'),
                $this->html->getSecureURL('sale/customer/update_address', '&customer_id='.$customer_id)
            );
        } elseif ($this->data['checkout']->getCart()->hasShipping() && !$this->data['checkout']->getShippingList()) {
            $this->data['error_warning'] = sprintf(
                $this->language->get('error_no_shipping_methods'),
                $this->html->getSecureURL('extension/extensions/shipping')
            );
        } elseif ($this->data['checkout']->getCart()->getProducts() && !$this->data['checkout']->getPaymentList()) {
            $this->data['error_warning'] = sprintf(
                $this->language->get('error_no_payments'),
                $this->html->getSecureURL('extension/extensions/payment')
            );
        }
        $this->extensions->hk_ValidateData($this, [__FUNCTION__]);
    }

    public function addProduct()
    {
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $post = $this->request->post;
        $checkout = $this->initCheckout($this->session->data['admin_order']);
        $this->data['product_key'] = $checkout->getCart()->add(
            $post['product_id'],
            $post['quantity'],
            $post['option'],
            H::preformatFloat($post['price'])
        );
        $this->session->data['admin_order']['cart'] = $checkout->getCart()->getCartData();
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
        abc_redirect($this->html->getSecureURL('sale/order/createOrder'));
    }

    public function removeProduct()
    {
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $checkout = $this->initCheckout($this->session->data['admin_order']);
        $checkout->getCart()->remove($this->request->get['product_key']);
        $this->session->data['admin_order']['cart'] = $checkout->getCart()->getCartData();
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
        abc_redirect($this->html->getSecureURL('sale/order/createOrder'));
    }

}
