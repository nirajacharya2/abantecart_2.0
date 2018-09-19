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
use abc\core\engine\ALanguage;
use abc\core\helper\AHelperUtils;
use abc\core\lib\AEncryption;
use abc\core\lib\AMail;
use H;

/**
 * Class ControllerPagesSaleCustomer
 *
 * @package abc\controllers\admin
 *
 * @property
 */
class ControllerPagesSaleCustomer extends AController
{
    public $data = [];
    public $error = [];
    protected $model;

    /*
     * @var array - key -s field name mask, value - requirement
     */
    public $address_fields =
        [
            'firstname'  => [
                'type'     => 'input',
                'required' => true,
            ],
            'lastname'   => [
                'type'     => 'input',
                'required' => true,
            ],
            'company'    => [
                'type'     => 'input',
                'required' => false,
            ],
            'address_1'  => [
                'type'     => 'input',
                'required' => true,
            ],
            'address_2'  => [
                'type'     => 'input',
                'required' => false,
            ],
            'city'       => [
                'type'     => 'input',
                'required' => true,
            ],
            'postcode'   => [
                'type'     => 'input',
                'required' => false,
            ],
            //note! this field is pair of country_id and zone_id
            'country_id' => [
                'type'     => 'zones',
                'required' => true,
            ],
        ];

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
            'href'    => $this->html->getSecureURL('sale/customer'),
            'text'    => $this->language->get('heading_title'),
            'current' => true,
        ]);

        //set store selector
        $this->view->assign('form_store_switch', $this->html->getStoreSwitcher());

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

        $grid_settings = [
            //id of grid
            'table_id'     => 'customer_grid',
            // url to load data from
            'url'          => $this->html->getSecureURL('listing_grid/customer'),
            'editurl'      => $this->html->getSecureURL('listing_grid/customer/update'),
            'update_field' => $this->html->getSecureURL('listing_grid/customer/update_field'),
            'sortname'     => 'name',
            'sortorder'    => 'asc',
            'multiselect'  => 'true',
            // actions
            'actions'      => [
                'actonbehalfof' => [
                    'text'   => $this->language->get('button_actas'),
                    'href'   => $this->html->getSecureURL('sale/customer/actonbehalf', '&customer_id=%ID%'),
                    'target' => 'new',
                ],
                'approve'       => [
                    'text' => $this->language->get('button_approve'),
                    'href' => $this->html->getSecureURL('sale/customer/approve', '&customer_id=%ID%'),
                ],
                'edit'          => [
                    'text'     => $this->language->get('text_edit'),
                    'href'     => $this->html->getSecureURL('sale/customer/update', '&customer_id=%ID%'),
                    'children' => array_merge([
                        'quickview'   => [
                            'text'  => $this->language->get('text_quick_view'),
                            'href'  => $this->html->getSecureURL('sale/customer/update', '&customer_id=%ID%'),
                            //quick view port URL
                            'vhref' => $this->html->getSecureURL('r/common/viewport/modal',
                                '&viewport_rt=sale/customer/update&customer_id=%ID%'),
                        ],
                        'details'     => [
                            'text' => $this->language->get('tab_customer_details'),
                            'href' => $this->html->getSecureURL('sale/customer/update', '&customer_id=%ID%'),
                        ],
                        'transaction' => [
                            'text' => $this->language->get('tab_transactions'),
                            'href' => $this->html->getSecureURL('sale/customer_transaction', '&customer_id=%ID%'),
                        ],
                        'note' => [
                            'href' => $this->html->getSecureURL('sale/customer/notes', '&customer_id=%ID%'),
                            'text' => $this->language->get('tab_customer_notes'),
                        ],
                        'create_order' => [
                            'text' => $this->language->get('text_create_order'),
                            'href' => $this->html->getSecureURL('sale/order/createOrder', '&customer_id=%ID%'),
                        ],
                    ], (array)$this->data['grid_edit_expand']),
                ],
                'save'          => [
                    'text' => $this->language->get('button_save'),
                ],
                'delete'        => [
                    'text' => $this->language->get('button_delete'),
                ],
            ],
            'grid_ready'   => 'grid_ready();',
        ];

        $this->load->model('setting/store');
        if (!$this->model_setting_store->isDefaultStore()) {
            $this->view->assign(
                'warning_actonbehalf',
                htmlspecialchars(
                    $this->language->get('warning_actonbehalf_additional_store'),
                    ENT_QUOTES,
                    ABC::env('APP_CHARSET')
                )
            );
        }

        $grid_settings['colNames'] = [
            $this->language->get('column_name'),
            $this->language->get('column_email'),
            $this->language->get('column_group'),
            $this->language->get('column_status'),
            $this->language->get('column_approved'),
            $this->language->get('text_order'),
        ];
        $grid_settings['colModel'] = [
            [
                'name'  => 'name',
                'index' => 'name',
                'width' => 160,
                'align' => 'center',
            ],
            [
                'name'  => 'email',
                'index' => 'email',
                'width' => 180,
                'align' => 'center',
            ],
            [
                'name'   => 'customer_group',
                'index'  => 'customer_group',
                'width'  => 80,
                'align'  => 'center',
                'search' => false,
            ],
            [
                'name'   => 'status',
                'index'  => 'status',
                'width'  => 110,
                'align'  => 'center',
                'search' => false,
            ],
            [
                'name'   => 'approved',
                'index'  => 'approved',
                'width'  => 110,
                'align'  => 'center',
                'search' => false,
            ],
            [
                'name'   => 'orders',
                'index'  => 'orders_count',
                'width'  => 70,
                'align'  => 'center',
                'search' => false,
            ],
        ];

        $this->loadModel('sale/customer_group');
        $results = $this->model_sale_customer_group->getCustomerGroups();
        $groups = ['' => $this->language->get('text_select_group'),];
        foreach ($results as $item) {
            $groups[$item['customer_group_id']] = $item['name'];
        }

        $statuses = [
            '' => $this->language->get('text_select_status'),
            1  => $this->language->get('text_enabled'),
            0  => $this->language->get('text_disabled'),
        ];

        $approved = [
            '' => $this->language->get('text_select_approved'),
            1  => $this->language->get('text_yes'),
            0  => $this->language->get('text_no'),
        ];

        $form = new AForm();
        $form->setForm([
            'form_name' => 'customer_grid_search',
        ]);

        //get search filter from cookie if requested
        $search_params = [];
        if ($this->request->get['saved_list']) {
            $grid_search_form = json_decode(html_entity_decode($this->request->cookie['grid_search_form']));
            if ($grid_search_form->table_id == $grid_settings['table_id']) {
                parse_str($grid_search_form->params, $search_params);
            }
        }

        $grid_search_form = [];
        $grid_search_form['id'] = 'customer_grid_search';
        $grid_search_form['form_open'] = $form->getFieldHtml([
            'type'   => 'form',
            'name'   => 'customer_grid_search',
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

        $grid_search_form['fields']['customer_group'] = $form->getFieldHtml([
            'type'    => 'selectbox',
            'name'    => 'customer_group',
            'options' => $groups,
        ]);
        $grid_search_form['fields']['status'] = $form->getFieldHtml([
            'type'    => 'selectbox',
            'name'    => 'status',
            'options' => $statuses,
        ]);
        $grid_search_form['fields']['approved'] = $form->getFieldHtml([
            'type'    => 'selectbox',
            'name'    => 'approved',
            'options' => $approved,
        ]);

        $grid_settings['search_form'] = true;

        $grid = $this->dispatch('common/listing_grid', [$grid_settings]);
        $this->view->assign('listing_grid', $grid->dispatchGetOutput());
        $this->view->assign('search_form', $grid_search_form);

        $this->document->setTitle($this->language->get('heading_title'));
        $this->view->assign('insert', $this->html->getSecureURL('sale/customer/insert'));
        $this->view->assign('help_url', $this->gen_help_url('customer_listing'));

        $this->processTemplate('pages/sale/customer_list.tpl');

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function insert()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->document->setTitle($this->language->get('heading_title'));
        if ($this->request->is_POST() && $this->validateForm()) {
            $customer_id = $this->model_sale_customer->addCustomer($this->request->post);
            $redirect_url = $this->html->getSecureURL('sale/customer/insert_address', '&customer_id='.$customer_id);
            $this->session->data['success'] = $this->language->get('text_success');
            abc_redirect($redirect_url);
        }
        $this->getForm();

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

        $customer_id = $this->request->get['customer_id'];
        if ($this->request->is_POST() && $this->validateForm($customer_id)) {

            if ((int)$this->request->post['approved']) {
                $customer_info = $this->model_sale_customer->getCustomer($customer_id);
                if (!$customer_info['approved'] && !$this->model_sale_customer->isSubscriber($customer_id)) {
                    $this->model_sale_customer->sendApproveMail($customer_id);
                }
            }
            $this->model_sale_customer->editCustomer($this->request->get['customer_id'], $this->request->post);
            $redirect_url = $this->html->getSecureURL('sale/customer/update', '&customer_id='.$customer_id);

            $this->session->data['success'] = $this->language->get('text_success');
            $this->extensions->hk_ProcessData($this, 'customer_update');
            abc_redirect($redirect_url);
        }

        $this->getForm($args);

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    protected function getForm($args = [])
    {
        $viewport_mode = isset($args[0]['viewport_mode']) ? $args[0]['viewport_mode'] : '';
        $customer_id = $this->request->get['customer_id'];

        $this->data['token'] = $this->session->data['token'];
        $this->data['error'] = $this->error;

        $this->document->initBreadcrumb([
            'href'      => $this->html->getSecureURL('index/home'),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);
        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('sale/customer'),
            'text'      => $this->language->get('heading_title'),
            'separator' => ' :: ',
        ]);

        $this->data['addresses'] = [];
        $customer_info = [];
        if (H::has_value($customer_id)) {
            //$this->model = Customer::find($customer_id);
            //$customer_info = $this->model->toArray();
            $customer_info = $this->model_sale_customer->getCustomer($customer_id);
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
            $this->data['addresses'] = $this->model_sale_customer->getAddressesByCustomerId($customer_id);
            if ($customer_info['last_login']
                && !in_array($customer_info['last_login'], ['0000-00-00 00:00:00', '1970-01-01 00:00:00'])) {
                $date = H::dateISO2Display($customer_info['last_login'],
                    $this->language->get('date_format_short').' '.$this->language->get('time_format'));
            } else {
                $date = $this->language->get('text_never');
            }
            $this->data['last_login'] = $this->language->get('text_last_login').' '.$date;
        }

        foreach ($this->data['addresses'] as &$a) {
            $a['href'] = $this->html->getSecureURL('sale/customer/update_address',
                '&customer_id='.$customer_id.'&address_id='.$a['address_id']);
            $a['title'] = $a['address_1'].' '.$a['address_2'];
            //mark default address
            if ($customer_info['address_id'] == $a['address_id']) {
                $a['default'] = 1;
            }
        }
        $this->data['add_address_url'] =
            $this->html->getSecureURL('sale/customer/update_address', '&customer_id='.$customer_id);

        //allow to change this list via hook
        $this->data['fields'] = array_merge([
            'loginname'         => 'required',
            'firstname'         => 'required',
            'lastname'          => 'required',
            'email'             => 'required',
            'telephone'         => 'required',
            'fax'               => 'required',
            'sms'               => null,
            'newsletter'        => null,
            'customer_group_id' => null,
            'status'            => null,
            'approved'          => null,
            'password'          => 'required',
        ],
            (array)$this->data['fields']);

        $fields = array_keys($this->data['fields']);
        foreach ($fields as $f) {
            if (isset ($this->request->post [$f])) {
                $this->data [$f] = $this->request->post [$f];
            } elseif (isset($customer_info)) {
                $this->data[$f] = $customer_info[$f];
            } else {
                $this->data[$f] = '';
            }
        }

        if (!isset($this->data['customer_group_id'])) {
            $this->data['customer_group_id'] = $this->config->get('config_customer_group_id');
        }
        if (!isset($this->data['status'])) {
            $this->data['status'] = 1;
        }
        if (!isset($this->data['password']) && isset($this->request->post['password'])) {
            $this->data['password'] = $this->request->post['password'];
        } else {
            $this->data['password'] = '';
        }

        //new customer or new address
        if (!isset($customer_id)) {
            $this->data['action'] = $this->html->getSecureURL('sale/customer/insert');
            $this->data['heading_title'] = $this->language->get('text_insert').$this->language->get('text_customer');
            $this->data['update'] = '';
            $form = new AForm('ST');
        } else {
            $this->data['customer_id'] = $customer_id;
            $this->data['action'] = $this->html->getSecureURL('sale/customer/update', '&customer_id='.$customer_id);
            $this->data['heading_title'] =
                $this->language->get('text_edit').$this->language->get('text_customer').' - '.$this->data['firstname']
                .' '
                .$this->data['lastname'];
            $this->data['update'] =
                $this->html->getSecureURL('listing_grid/customer/update_field', '&id='.$customer_id);
            $form = new AForm('HS');

            $this->data['reset_password'] = $this->html->buildElement(
                [
                    'type' => 'button',
                    'name' => 'reset_password_button',
                    'href' => $this->html->getSecureURL(
                                'sale/customer/resetPassword',
                                '&customer_id='.$customer_id
                    ),
                    'title' => $this->language->get('text_resend_password')
                ]
            );
        }

        $this->document->addBreadcrumb([
            'href'      => $this->data['action'],
            'text'      => $this->data['heading_title'],
            'separator' => ' :: ',
            'current'   => true,
        ]);

        $this->data['tabs']['general'] = [
            'href'   => $this->html->getSecureURL('sale/customer/update', '&customer_id='.$customer_id),
            'text'   => $this->language->get('tab_customer_details'),
            'active' => true,
        ];
        if (H::has_value($customer_id)) {
            $this->data['tabs'][] = [
                'href' => $this->html->getSecureURL('sale/customer_transaction', '&customer_id='.$customer_id),
                'text' => $this->language->get('tab_transactions'),
            ];
            $this->data['tabs'][] = [
                'href' => $this->html->getSecureURL('sale/customer/notes', '&customer_id='.$customer_id),
                'text' => $this->language->get('tab_customer_notes'),
            ];
        }

        $this->load->model('setting/store');
        if (!$this->model_setting_store->isDefaultStore()) {
            $this->data['warning_actonbehalf'] =
                htmlspecialchars($this->language->get('warning_actonbehalf_additional_store'), ENT_QUOTES,
                    ABC::env('APP_CHARSET'));
        }

        $this->data['actas'] = $this->html->buildElement([
            'type'   => 'button',
            'text'   => $this->language->get('button_actas'),
            'href'   => $this->html->getSecureURL('sale/customer/actonbehalf', '&customer_id='.$customer_id),
            'target' => 'new',
        ]);
        $this->data['message'] = $this->html->buildElement([
            'type'   => 'button',
            'text'   => $this->language->get('button_message'),
            'href'   => $this->html->getSecureURL('sale/contact', '&to[]='.$customer_id),
            'target' => 'new',
        ]);

        $form->setForm([
            'form_name' => 'cgFrm',
            'update'    => $this->data['update'],
        ]);

        $this->data['form']['id'] = 'cgFrm';
        $this->data['form']['form_open'] = $form->getFieldHtml([
            'type'   => 'form',
            'name'   => 'cgFrm',
            'attr'   => 'data-confirm-exit="true" class="form-horizontal"',
            'action' => $this->data['action'],
        ]);
        $this->data['form']['submit'] = $form->getFieldHtml([
            'type' => 'button',
            'name' => 'submit',
            'text' => $this->language->get('button_save'),
        ]);
        $this->data['form']['reset'] = $form->getFieldHtml([
            'type' => 'button',
            'name' => 'reset',
            'text' => $this->language->get('button_reset'),
        ]);

        $this->data['form']['fields']['details']['status'] = $form->getFieldHtml([
            'type'  => 'checkbox',
            'name'  => 'status',
            'value' => $this->data['status'],
            'style' => 'btn_switch',
        ]);
        $this->data['form']['fields']['details']['approved'] = $form->getFieldHtml([
            'type'  => 'checkbox',
            'name'  => 'approved',
            'value' => $this->data['approved'],
            'style' => 'btn_switch',
        ]);

        $required_input = [];
        foreach ($this->data['fields'] as $field_name => $required) {
            if ($required) {
                $required_input[] = $field_name;
            }
        }

        foreach ($required_input as $f) {
            if ($viewport_mode == 'modal' && in_array($f, ['password'])) {
                continue;
            }

            $field_type = ($f == 'password' ? 'passwordset' : 'input');
            $field_type = ($f == 'telephone' ? 'phone' : $field_type);

            $this->data['form']['fields']['details'][$f] = $form->getFieldHtml([
                'type'     => $field_type,
                'name'     => $f,
                'value'    => $this->data[$f],
                'required' => (in_array($f, ['password', 'fax', 'telephone']) ? false : true),
                'style'    => ($f == 'password' ? 'small-field' : ''),
            ]);
        }

        //get only active IM drivers
        $im_drivers = $this->im->getIMDriverObjects();
        if ($im_drivers) {
            foreach ($im_drivers as $protocol => $driver_obj) {
                /**
                 * @var \abc\core\lib\AMailIM $driver_obj
                 */
                if (!is_object($driver_obj) || $protocol == 'email') {
                    continue;
                }
                $fld = $driver_obj->getURIField($form, $this->data[$protocol]);
                $this->data['form']['fields']['details'][$protocol] = $fld;
                $this->data['entry_'.$protocol] = $fld->label_text;
                $this->data['error_'.$protocol] = $this->error[$protocol];
            }
        }

        $this->data['form']['fields']['details']['newsletter'] = $form->getFieldHtml([
            'type'    => 'selectbox',
            'name'    => 'newsletter',
            'value'   => $this->data['newsletter'],
            'options' => [
                1 => $this->language->get('text_enabled'),
                0 => $this->language->get('text_disabled'),
            ],
        ]);

        $this->loadModel('sale/customer_group');
        $results = $this->model_sale_customer_group->getCustomerGroups();
        $groups = ['' => $this->language->get('text_select_group'),];
        foreach ($results as $item) {
            $groups[$item['customer_group_id']] = $item['name'];
        }

        $this->data['form']['fields']['details']['customer_group'] = $form->getFieldHtml([
            'type'    => 'selectbox',
            'name'    => 'customer_group_id',
            'value'   => $this->data['customer_group_id'],
            'options' => $groups,
        ]);

        $this->data['section'] = 'details';
        $this->data['tabs']['general']['active'] = true;

        $saved_list_data = json_decode(html_entity_decode($this->request->cookie['grid_params']));
        if ($saved_list_data->table_id == 'customer_grid') {
            $this->data['list_url'] = $this->html->getSecureURL('sale/customer', '&saved_list=customer_grid');
        }

        $this->view->assign('help_url', $this->gen_help_url('customer_edit'));
        $this->loadModel('sale/customer_transaction');
        $balance = $this->model_sale_customer_transaction->getBalance($customer_id);
        $currency = $this->currency->getCurrency($this->config->get('config_currency'));

        $this->data['balance'] = $this->language->get('text_balance')
            .' '.$currency['symbol_left']
            .round($balance, 2)
            .$currency['symbol_right'];
        $this->view->batchAssign($this->data);

        if ($viewport_mode == 'modal') {
            $tpl = 'responses/viewport/modal/sale/customer_form.tpl';
        } else {
            $tpl = 'pages/sale/customer_form.tpl';
        }

        $this->processTemplate($tpl);

    }

    public function insert_address()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->document->setTitle($this->language->get('heading_title'));

        $customer_id = $this->request->get['customer_id'];
        if ($this->request->is_POST() && $this->validateAddressForm()) {
            $address_id = $this->model_sale_customer->addAddress($customer_id, $this->request->post);
            $redirect_url = $this->html->getSecureURL(
                'sale/customer/update',
                '&customer_id='.$customer_id.'&address_id='.$address_id
            );

            //do we need to update default address?
            if ($this->request->post['default']) {
                $this->model_sale_customer->setDefaultAddress($customer_id, $address_id);
            }

            $this->session->data['success'] = $this->language->get('text_success');
            abc_redirect($redirect_url);
        }

        $this->getAddressForm();

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function update_address()
    {

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

        $customer_id = $this->request->get['customer_id'];
        $address_id = $this->request->get['address_id'];
        if ($this->request->is_POST() && $this->validateAddressForm()) {
            //do we need to update default address?
            if ($this->request->post['default']) {
                $this->model_sale_customer->setDefaultAddress($customer_id, $address_id);
            }
            $this->model_sale_customer->editAddress($customer_id, $address_id, $this->request->post);
            $redirect_url = $this->html->getSecureURL(
                'sale/customer/update_address',
                '&customer_id='.$customer_id.'&address_id='.$address_id
            );

            $this->session->data['success'] = $this->language->get('text_success');
            abc_redirect($redirect_url);
        }

        $this->getAddressForm();

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    protected function getAddressForm()
    {

        $address_id = $this->request->get['address_id'];
        $customer_id = $this->request->get['customer_id'];

        $this->data['token'] = $this->session->data['token'];
        $this->data['error'] = $this->error;

        $this->document->initBreadcrumb([
            'href'      => $this->html->getSecureURL('index/home'),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);
        $this->document->addBreadcrumb([
            'href'    => $this->html->getSecureURL('sale/customer'),
            'text'    => $this->language->get('heading_title'),
            'current' => true,
        ]);

        $this->data['addresses'] = [];

        $customer_info = [];

        if (H::has_value($customer_id)) {
            $customer_info = $this->model_sale_customer->getCustomer($customer_id);
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
            $this->data['addresses'] = $this->model_sale_customer->getAddressesByCustomerId($customer_id);
        }

        //current edited address
        $current_address = [];
        if ($this->data['addresses']) {
            foreach ($this->data['addresses'] as &$a) {
                $a['href'] = $this->html->getSecureURL(
                    'sale/customer/update_address',
                    '&customer_id='.$customer_id.'&address_id='.$a['address_id']
                );
                $a['title'] = $a['address_1'].' '.$a['address_2'];
                //mark default address
                if ($customer_info['address_id'] == $a['address_id']) {
                    $a['default'] = 1;
                }
                if ($address_id == $a['address_id']) {
                    $current_address = $a;
                    $this->data['current_address'] = $a['title'];
                }
            }
        }
        if ($this->request->is_POST()) {
            $current_address = $this->request->post;
        }

        $this->loadModel('localisation/country');
        $this->data['countries'] = $this->model_localisation_country->getCountries();
        $this->data['customer_id'] = $customer_id;

        $this->data['add_address_url'] = $this->html->getSecureURL(
            'sale/customer/update_address',
            '&customer_id='.$customer_id
        );
        $this->data['category_products'] = $this->html->getSecureURL('product/product/category');
        $this->data['common_zone'] = $this->html->getSecureURL('common/zone');

        if (!H::has_value($address_id)) {
            //new address
            $this->data['action'] = $this->html->getSecureURL(
                'sale/customer/insert_address',
                '&customer_id='.$customer_id
            );
            $this->data['tab_customer_address'] = $this->language->get('text_add_address');
            $this->data['heading_title'] = $this->language->get('text_insert').$this->language->get('text_customer');
            $this->data['update'] = '';
            $form = new AForm('ST');
        } else {
            //edit address
            $this->data['heading_title'] = $this->language->get('text_edit_address');
            $this->data['action'] = $this->html->getSecureURL(
                'sale/customer/update_address',
                '&customer_id='.$customer_id.'&address_id='.$address_id);
            $this->data['update'] = $this->html->getSecureURL('listing_grid/customer/update_field',
                '&id='.$customer_id.'&address_id='.$address_id);
            $this->data['tab_customer_address'] = $this->language->get('text_edit_address');
            $form = new AForm('HS');
        }

        $this->document->addBreadcrumb([
            'href'    => $this->data['action'],
            'text'    => $this->data['heading_title'],
            'current' => true,
        ]);

        $this->data['tabs']['general'] = [
            'href'   => $this->html->getSecureURL('sale/customer/update', '&customer_id='.$customer_id),
            'text'   => $this->language->get('tab_customer_details'),
            'active' => true,
        ];

        if (H::has_value($customer_id)) {
            $this->data['tabs'][] = [
                'href' => $this->html->getSecureURL('sale/customer_transaction', '&customer_id='.$customer_id),
                'text' => $this->language->get('tab_transactions'),
            ];
        }

        $this->data['actas'] = $this->html->buildElement([
            'type'   => 'button',
            'text'   => $this->language->get('button_actas'),
            'href'   => $this->html->getSecureURL('sale/customer/actonbehalf', '&customer_id='.$customer_id),
            'target' => 'new',
        ]);

        $form->setForm([
            'form_name' => 'cgFrm',
            'update'    => $this->data['update'],
        ]);

        $this->data['form']['id'] = 'cgFrm';
        $this->data['form']['form_open'] = $form->getFieldHtml([
            'type'   => 'form',
            'name'   => 'cgFrm',
            'attr'   => 'data-confirm-exit="true" class="form-horizontal"',
            'action' => $this->data['action'],
        ]);
        $this->data['form']['submit'] = $form->getFieldHtml([
            'type' => 'button',
            'name' => 'submit',
            'text' => $this->language->get('button_save'),
        ]);
        $this->data['form']['reset'] = $form->getFieldHtml([
            'type' => 'button',
            'name' => 'reset',
            'text' => $this->language->get('button_reset'),
        ]);

        foreach ($current_address as $name => $value) {
            $this->data['address'][$name] = $value;
        }

        $this->data['section'] = 'address';

        $this->view->assign('help_url', $this->gen_help_url('customer_edit'));
        $this->loadModel('sale/customer_transaction');
        $balance = $this->model_sale_customer_transaction->getBalance($customer_id);
        $this->data['balance'] = $this->language->get('text_balance').' '.$this->currency->format($balance,
                $this->config->get('config_currency'));

        //note: Only allow to delete or change if not default
        if (!$current_address['default']) {
            if (H::has_value($address_id)) {
                $this->data['form']['delete'] = $form->getFieldHtml([
                    'type' => 'button',
                    'name' => 'delete',
                    'href' => $this->html->getSecureURL('sale/customer/delete_address',
                        '&customer_id='.$customer_id.'&address_id='.$address_id),
                    'text' => $this->language->get('button_delete'),
                ]);
            }

            $this->data['form']['fields']['address']['default'] = $form->getFieldHtml([
                'type'  => 'checkbox',
                'name'  => 'default',
                'value' => $current_address['default'],
                'style' => 'btn_switch',
            ]);
        }
        foreach ($this->address_fields as $name => $desc) {
            $fld_array = [
                'type'     => $desc['type'],
                'name'     => $name,
                'value'    => $this->data['address'][$name],
                'required' => $desc['required'],
            ];
            if ($desc['type'] == 'zones') {
                $fld_array['submit_mode'] = 'id';
                $fld_array['zone_name'] = $this->data['address']['zone'];
                $fld_array['zone_value'] = $this->data['address']['zone_id'];
            }
            $this->data['form']['fields']['address'][$name] = $form->getFieldHtml($fld_array);
        }

        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/sale/customer_form.tpl');
    }

    public function approve()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('mail/customer');

        if (!$this->user->canModify('sale/customer')) {
            $this->session->data['error'] = $this->language->get('error_permission');
            abc_redirect($this->html->getSecureURL('sale/customer'));
        }

        if (!isset($this->request->get['customer_id'])) {
            abc_redirect($this->html->getSecureURL('sale/customer'));
        }

        $customer_id = $this->request->get['customer_id'];

        $this->model_sale_customer->editCustomerField($customer_id, 'approved', true);
        if (!$this->model_sale_customer->isSubscriber($customer_id)) {
            $this->model_sale_customer->sendApproveMail($customer_id);
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        abc_redirect($this->html->getSecureURL('sale/customer'));
    }

    public function actonbehalf()
    {

        $this->extensions->hk_InitData($this, __FUNCTION__);
        if (isset($this->request->get['customer_id'])) {
            //NOTE: if need to act on additional store - redirect to it's admin side.
            // and then to storefront because cross-domain restriction for session cookie
            $this->loadModel('setting/store');
            $store_settings = $this->model_setting_store->getStore($this->session->data['current_store_id']);
            if ($this->config->get('config_url')
                != $this->model_setting_store->getStoreURL($this->session->data['current_store_id'])) {
                if ($store_settings) {
                    if ($store_settings['config_ssl']) {
                        $add_store_url = $store_settings['config_ssl_url'].'?s='.ABC::env('ADMIN_SECRET')
                            .'&rt=sale/customer/actonbehalf&customer_id='.$this->request->get['customer_id'];
                    } else {
                        $add_store_url = $store_settings['config_url'].'?s='.ABC::env('ADMIN_SECRET')
                            .'&rt=sale/customer/actonbehalf&customer_id='.$this->request->get['customer_id'];
                    }
                    abc_redirect($add_store_url);
                }
            } else {
                H::startStorefrontSession($this->user->getId(),
                    ['customer_id' => $this->request->get['customer_id']]);
                if ($store_settings['config_ssl']) {
                    abc_redirect($this->html->getCatalogURL('account/account', '', '', true));
                } else {
                    abc_redirect($this->html->getCatalogURL('account/account'));
                }
            }
        }

        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        abc_redirect($this->html->getSecureURL('sale/customer'));
    }

    public function delete_address()
    {
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->view->assign('error_warning', $this->session->data['warning']);
        if (isset($this->session->data['warning'])) {
            unset($this->session->data['warning']);
        }
        $this->view->assign('success', $this->session->data['success']);
        if (isset($this->session->data['success'])) {
            unset($this->session->data['success']);
        }

        $customer_id = $this->request->get['customer_id'];
        $address_id = $this->request->get['address_id'];
        if (H::has_value($customer_id) && H::has_value($address_id)) {
            //check if this is a default address. Do not allow to delete
            $customer_info = $this->model_sale_customer->getCustomer($customer_id);
            if ($customer_info['address_id'] == $address_id) {
                $this->error['warning'] = $this->language->get('error_delete_default');
                $this->getAddressForm();
            } else {
                $this->loadModel('sale/customer_group');
                $this->model_sale_customer->deleteAddress($customer_id, $address_id);
                $this->session->data['success'] = $this->language->get('text_success');
                abc_redirect($this->html->getSecureURL('sale/customer/update', '&customer_id='.$customer_id));
            }
        }
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    /**
     * @param null $customer_id
     *
     * @return bool
     * @throws \Exception
     */
    protected function validateForm($customer_id = null)
    {
        if (!$this->user->canModify('sale/customer')) {
            $this->error['warning'] = $this->language->get('error_permission');

            return false;
        }

        $data = $this->request->post;

        $login_name_pattern = '/^[\w._-]+$/i';
        if ((mb_strlen($data['loginname']) < 5 || mb_strlen($data['loginname']) > 64)
            || (!preg_match($login_name_pattern, $data['loginname']) && $this->config->get('prevent_email_as_login'))
        ) {
            $this->error['loginname'] = $this->language->get('error_loginname');
            //check uniqueness of login name
        } else {
            if (!$this->model_sale_customer->is_unique_loginname($data['loginname'], $customer_id)) {
                $this->error['loginname'] = $this->language->get('error_loginname_notunique');
            }
        }

        if (mb_strlen($data['email']) > 96 || !preg_match(ABC::env('EMAIL_REGEX_PATTERN'), $data['email'])) {
            $this->error['email'] = $this->language->get('error_email');
        }

        if (mb_strlen($data['telephone']) > 32) {
            $this->error['telephone'] = $this->language->get('error_telephone');
        }

        if (($data['password']) || (!isset($this->request->get['customer_id']))) {
            if (mb_strlen($data['password']) < 4) {
                $this->error['password'] = $this->language->get('error_password');
            }

            if (!$this->error['password'] && $data['password'] != $data['password_confirm']) {
                $this->error['password'] = $this->language->get('error_confirm');
            }
        }

        if (mb_strlen($data['firstname']) < 1 || mb_strlen($data['firstname']) > 32) {
            $this->error['firstname'] = $this->language->get('error_firstname');
        }

        if (mb_strlen($data['lastname']) < 1 || mb_strlen($data['lastname']) > 32) {
            $this->error['lastname'] = $this->language->get('error_lastname');
        }

        //validate IM URIs
        //get only active IM drivers
        $im_drivers = $this->im->getIMDriverObjects();
        if ($im_drivers) {
            foreach ($im_drivers as $protocol => $driver_obj) {
                /**
                 * @var \abc\core\lib\AMailIM $driver_obj
                 */
                if (!is_object($driver_obj) || $protocol == 'email') {
                    continue;
                }
                $result = $driver_obj->validateURI($data[$protocol]);
                if (!$result) {
                    $this->error[$protocol] = implode('<br>', $driver_obj->errors);
                }
            }
        }

        $this->extensions->hk_ValidateData($this);

        if (!$this->error) {
            return true;
        } else {
            $this->error['warning'] = implode('<br>', $this->error);
            return false;
        }
    }

    /**
     * @return bool
     */
    protected function validateAddressForm()
    {
        if (!$this->user->canModify('sale/customer')) {
            $this->error['warning'] = $this->language->get('error_permission');

            return false;
        }

        if (mb_strlen($this->request->post['address_1']) < 1) {
            $this->error['address_1'] = $this->language->get('error_address_1');
        }
        if (mb_strlen($this->request->post['city']) < 1) {
            $this->error['city'] = $this->language->get('error_city');
        }
        if (empty($this->request->post['country_id']) || $this->request->post['country_id'] == 'FALSE') {
            $this->error['country_id'] = $this->language->get('error_country');
        }
        if (empty($this->request->post['zone_id']) || $this->request->post['zone_id'] == 'FALSE') {
            $this->error['zone_id'] = $this->language->get('error_zone');
        }

        if (mb_strlen($this->request->post['firstname']) < 1 || mb_strlen($this->request->post['firstname']) > 32) {
            $this->error['firstname'] = $this->language->get('error_firstname');
        }

        if (mb_strlen($this->request->post['lastname']) < 1 || mb_strlen($this->request->post['lastname']) > 32) {
            $this->error['lastname'] = $this->language->get('error_lastname');
        }

        $this->extensions->hk_ValidateData($this);

        if (!$this->error) {
            return true;
        } else {
            $this->error['warning'] = implode('<br>', $this->error);
            return false;
        }
    }


    public function resetPassword()
    {
        $customer_id = (int)$this->request->get['customer_id'];
        $this->loadLanguage('sale/customer');

        if (!$this->user->canModify('sale/customer')) {
            $this->session->data['warning'] = sprintf(
                                        $this->language->get('error_permission_modify'),
                                        'sale/customer'
            );
            abc_redirect(
                $this->html->getSecureURL(
                    'sale/customer/update',
                    '&customer_id='.$customer_id
            ));
        }
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadModel('sale/customer');
        $customer_info = $this->model_sale_customer->getCustomer($customer_id);

        $error_text  = $this->validateBeforePasswordReset($customer_info);
        if($error_text){
            $this->session->data['warning'] = $error_text;
            abc_redirect($this->html->getSecureURL('sale/customer/update','&customer_id='.$customer_id));
        }

        $code = H::genToken(32);
        //save password reset code
        $this->loadModel('account/customer','storefront')->updateOtherData($customer_id, ['password_reset' => $code]);
        //build reset link
        $enc = new AEncryption($this->config->get('encryption_key'));
        $rtoken = $enc->encrypt($customer_id.'::'.$code);

        $link = $this->html->getSecureURL('account/forgotten/reset', '&rtoken=' . $rtoken, null, 'storefront');

        $language = new ALanguage($this->registry, $this->language->getLanguageCode(),0);
        $language->load('mail/account_forgotten');

        $subject = sprintf($language->get('text_subject'), $this->config->get('store_name'));
        $message = sprintf($this->language->get('text_password_was_reset'), $this->config->get('store_name')) . "\n\n";
        $message .= $language->get('text_password') . "\n\n";
        $message .= $link;

        $mail = new AMail( $this->config );
        $mail->setTo($customer_info['email']);
        $mail->setFrom($this->config->get('store_main_email'));
        $mail->setSender($this->config->get('store_name'));
        $mail->setSubject($subject);
        $mail->setText(html_entity_decode($message, ENT_QUOTES, ABC::env('APP_CHARSET')));
        $result = $mail->send();
        if(!$result) {
            $this->session->data['warning'] = $this->language->get('error_reset_link_not_sent');
        }else {
            $this->session->data['success'] = $this->language->get('text_password_reset_success');
            $this->extensions->hk_UpdateData($this, __FUNCTION__);
        }
        abc_redirect($this->html->getSecureURL('sale/customer/update','&customer_id='.$customer_id));
    }

    protected function validateBeforePasswordReset($customer_info)
    {
        if(!$customer_info){
            return $this->language->get('error_unknown_customer');
        }elseif(!$customer_info['email']){
            return $this->language->get('error_no_email');
        }elseif(!$customer_info['status'] || !$customer_info['approved']){
            return $this->language->get('error_disabled_customer');
        }
        return '';
    }

    public function notes() {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->data = [];
        $this->document->setTitle($this->language->get('heading_title'));

        $this->loadModel('sale/customer_note');

        if ($this->request->is_POST()) {
            $data = [
                'user_id'=> $this->user->getId(),
                'customer_id' => $this->request->get['customer_id'],
            ];
            $data = array_merge($data, $this->request->post);
           if ($this->model_sale_customer_note->addNote($data))
            $this->session->data['success'] = $this->language->get('text_success');
            abc_redirect($this->html->getSecureURL('sale/customer/notes',
                '&customer_id='.$this->request->get['customer_id']));
        }

        if (isset($this->request->get['customer_id'])) {
            $customer_id = (int)$this->request->get['customer_id'];
        } else {
            $customer_id = 0;
        }

        $customer_info = $this->model_sale_customer->getCustomer($customer_id);

        if (empty($customer_info)) {
            $this->session->data['error'] = $this->language->get('error_customer_load');
            abc_redirect($this->html->getSecureURL('sale/customer'));
        }

        $this->document->initBreadcrumb([
            'href'      => $this->html->getSecureURL('index/home'),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);

        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('sale/customer'),
            'text'      => $this->language->get('heading_title'),
            'separator' => ' :: ',
        ]);
        $this->document->addBreadcrumb( array(
            'href'      => $this->html->getSecureURL( 'sale/customer/update', '&customer_id='.$customer_id ),
            'text'      => $this->language->get( 'text_edit' ).' '.$this->language->get( 'text_customer' ).' - '.$customer_info['firstname'].' '.$customer_info['lastname'],
            'separator' => ' :: ',
        ) );
        $this->document->addBreadcrumb( array(
            'href'      => $this->html->getSecureURL( 'sale/customer_notes', '&customer_id='.$customer_id ),
            'text'      => $this->language->get( 'heading_title_notes' ),
            'separator' => ' :: ',
            'current'   => true,
        ) );

        $this->data['tabs']['general'] = array(
            'href' => $this->html->getSecureURL( 'sale/customer/update', '&customer_id='.$customer_id ),
            'text' => $this->language->get( 'tab_customer_details' ),
        );
        $this->data['tabs'][] = array(
            'href'   => $this->html->getSecureURL( 'sale/customer_transaction', '&customer_id='.$customer_id ),
            'text'   => $this->language->get( 'tab_transactions' ),
        );
        $this->data['tabs'][] = [
            'href' => $this->html->getSecureURL('sale/customer/notes', '&customer_id='.$customer_id),
            'text' => $this->language->get('tab_customer_notes'),
            'active' => true,
        ];

        if (isset($this->session->data['success'])) {
            $this->data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $this->data['success'] = '';
        }

        $this->data['notes'] = $this->model_sale_customer_note->getNotes($customer_id);


        $this->data['action'] = $this->html->getSecureURL('sale/customer/notes', '&customer_id='.$customer_id);
        $this->data['form_title'] = $this->language->get('text_edit').' '.$this->language->get('tab_note');
        $form = new AForm('ST');

        $form->setForm([
            'form_name' => 'noteFrm',
            'update'    => $this->data['update'],
        ]);

        $this->data['form']['id'] = 'orderFrm';
        $this->data['form']['form_open'] = $form->getFieldHtml([
            'type'   => 'form',
            'name'   => 'noteFrm',
            'attr'   => 'data-confirm-exit="true" class="aform form-horizontal"',
            'action' => $this->data['action'],
        ]);
        $this->data['form']['submit'] = $form->getFieldHtml([
            'type'  => 'button',
            'name'  => 'submit',
            'text'  => $this->language->get('button_add_note'),
            'style' => 'button1',
        ]);
        $this->data['form']['cancel'] = $form->getFieldHtml([
            'type'  => 'button',
            'name'  => 'cancel',
            'text'  => $this->language->get('button_cancel'),
            'style' => 'button2',
        ]);
        $this->data['form']['fields']['note'] = $form->getFieldHtml([
            'type'  => 'textarea',
            'name'  => 'note',
            'style' => 'large-field',
        ]);



        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/sale/customer_note.tpl');

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

    }
}