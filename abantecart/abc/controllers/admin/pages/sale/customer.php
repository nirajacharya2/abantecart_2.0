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
use abc\models\admin\ModelSaleCustomerNote;
use abc\models\customer\Address;
use abc\models\customer\Customer;
use abc\models\customer\CustomerGroup;
use abc\models\customer\CustomerNotes;
use abc\models\customer\CustomerTransaction;
use abc\models\order\Order;
use abc\models\system\Store;
use abc\modules\events\ABaseEvent;
use H;
use Illuminate\Validation\ValidationException;

/**
 * Class ControllerPagesSaleCustomer
 *
 * @package abc\controllers\admin
 * @property ModelSaleCustomerNote $model_sale_customer_note
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
                        'quickview'    => [
                            'text'  => $this->language->get('text_quick_view'),
                            'href'  => $this->html->getSecureURL('sale/customer/update', '&customer_id=%ID%'),
                            //quick view port URL
                            'vhref' => $this->html->getSecureURL('r/common/viewport/modal',
                                '&viewport_rt=sale/customer/update&customer_id=%ID%'),
                        ],
                        'details'      => [
                            'text' => $this->language->get('tab_customer_details'),
                            'href' => $this->html->getSecureURL('sale/customer/update', '&customer_id=%ID%'),
                        ],
                        'transaction'  => [
                            'text' => $this->language->get('tab_transactions'),
                            'href' => $this->html->getSecureURL('sale/customer_transaction', '&customer_id=%ID%'),
                        ],
                        'note'         => [
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

        if ($this->config->get('config_save_customer_communication')) {
            $grid_settings_part1 = array_slice($grid_settings['actions']['edit']['children'], 0, 4);
            $grid_settings_part2 = array_slice($grid_settings['actions']['edit']['children'], 4, 1);
            $grid_settings_part1['communication'] = [
                'href' => $this->html->getSecureURL('sale/customer/communications', '&customer_id=%ID%'),
                'text' => $this->language->get('tab_customer_communications'),
            ];
            $grid_settings['actions']['edit']['children'] = array_merge($grid_settings_part1, $grid_settings_part2);
        }

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
        if ($this->request->is_POST()) {
            $data = $this->request->post;
            if( $this->validateForm($data) ) {
                $customer = new Customer($data);
                $customer->save();
                $customer_id = (int)$customer->customer_id;
                $redirect_url = $this->html->getSecureURL('sale/customer/insert_address', '&customer_id='.$customer_id);
                $this->session->data['success'] = $this->language->get('text_success');
                $this->extensions->hk_ProcessData($this, 'customer_insert', ['customer_id' => $customer_id]);
                abc_redirect($redirect_url);
            }
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

        $customer_id = (int)$this->request->get['customer_id'];
        /**
         * @var Customer $customer
         */
        $customer = Customer::find($customer_id);

        if(!$customer){
            abc_redirect($this->html->getSecureURL('sale/customer'));
        }

        if ($customer && $this->request->is_POST() && $this->validateForm($this->request->post, $customer_id)) {
            if ((int)$this->request->post['approved']) {
                if (!$customer->approved && !$customer->isSubscriber()) {
                    H::event('admin\sendApprovalEmail', [new ABaseEvent($customer->toArray())]);
                }
            }
            $customer->update($this->request->post);
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
        $customer_id = (int)$this->request->get['customer_id'];

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
        if ($customer_id) {

            $customer = Customer::with(['addresses'])
                                 ->where('customer_id', $customer_id)
                                 ->first();
            if($customer) {
                $customer_info = $customer->toArray();
                $customer_info['orders_count'] = Order::where('customer_id', '=', $customer_id)->where('order_status_id', '>',0)->get()->count();
            }
            $this->data['button_orders_count'] = $this->html->buildElement(
                [
                    'type'  => 'button',
                    'name'  => 'view orders',
                    'text'  => $this->language->get('text_total_order').' '.(int)$customer_info['orders_count'],
                    'style' => 'button2',
                    'href'  => $this->html->getSecureURL('sale/order', '&customer_id='.$customer_id),
                    'title' => $this->language->get('text_view').' '.$this->language->get('tab_history'),
                ]
            );
            $this->data['addresses'] = $customer_info['addresses'];
            if ($customer_info['last_login']) {
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
        $this->data['add_address_url'] = $this->html->getSecureURL(
            'sale/customer/update_address',
            '&customer_id='.$customer_id
        );

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
        if (!$customer_id) {
            $this->data['action'] = $this->html->getSecureURL('sale/customer/insert');
            $this->data['heading_title'] = $this->language->get('text_insert').$this->language->get('text_customer');
            $this->data['update'] = '';
            $formType = $this->data['new_customer_form_type'] ?: 'ST';
            $form = new AForm($formType);
        } else {
            $this->data['customer_id'] = $customer_id;
            $this->data['action'] = $this->html->getSecureURL('sale/customer/update', '&customer_id='.$customer_id);
            $this->data['heading_title'] =
                $this->language->get('text_edit').$this->language->get('text_customer').' - '.$this->data['firstname']
                .' '
                .$this->data['lastname'];
            $this->data['update'] = $this->html->getSecureURL(
                'listing_grid/customer/update_field',
                '&id='.$customer_id
            );
            $formType = $this->data['edit_customer_form_type'] ?: 'HS';
            $form = new AForm($formType);

            $this->data['reset_password'] = $this->html->buildElement(
                [
                    'type'  => 'button',
                    'name'  => 'reset_password_button',
                    'href'  => $this->html->getSecureURL(
                        'listing_grid/customer/resetPassword',
                        '&customer_id='.$customer_id
                    ),
                    'title' => $this->language->get('text_resend_password'),
                ]
            );
        }

        $this->document->addBreadcrumb([
            'href'      => $this->data['action'],
            'text'      => $this->data['heading_title'],
            'separator' => ' :: ',
            'current'   => true,
        ]);

        if ($customer_id) {
            $this->getTabs($customer_id, 'general');
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
        $this->data['auditLog'] = $this->html->buildElement([
            'type'   => 'button',
            'text'  => $this->language->get('text_audit_log'),
            'href'  => $this->html->getSecureURL('tool/audit_log', '&modal_mode=1&auditable_type=Customer&auditable_id='.$customer_id),
            //quick view port URL
            'vhref' => $this->html->getSecureURL(
                'r/common/viewport/modal',
                '&viewport_rt=tool/audit_log&modal_mode=1&auditable_type=Customer&auditable_id='.$customer_id),
        ]);
        $this->data['message'] = $this->html->buildElement([
            'type'   => 'button',
            'text'   => $this->language->get('button_message'),
            'href'   => $this->html->getSecureURL('sale/contact', '&to[]='.$customer_id),
            'target' => 'new',
        ]);
        $this->data['new_order'] = $this->html->buildElement([
            'type'   => 'button',
            'text'   => $this->language->get('text_create_order'),
            'href'   => $this->html->getSecureURL('sale/order/createOrder', '&customer_id='.$customer_id),
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
            'csrf' => true,
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

        $groups =
            ['' => $this->language->get('text_select_group')]
            +
            array_column(CustomerGroup::all()->toArray(), 'name', 'customer_group_id');

        $this->data['entry_customer_group_id'] = $this->language->get('entry_customer_group');
        $this->data['form']['fields']['details']['customer_group_id'] = $form->getFieldHtml([
            'type'    => 'selectbox',
            'name'    => 'customer_group_id',
            'value'   => $this->data['customer_group_id'],
            'options' => $groups,
        ]);

        $this->data['entry_store_id'] = $this->language->get('tab_store');
        $this->data['form']['fields']['details']['store_id'] = $form->getFieldHtml([
            'type'    => 'selectbox',
            'name'    => 'store_id',
            'value'   => $this->data['store_id'],
            'options' => array_column(Store::all()->toArray(), 'name', 'store_id')
        ]);

        $this->data['section'] = 'details';

        $saved_list_data = json_decode(html_entity_decode($this->request->cookie['grid_params']));
        if ($saved_list_data->table_id == 'customer_grid') {
            $this->data['list_url'] = $this->html->getSecureURL('sale/customer', '&saved_list=customer_grid');
        }

        $this->view->assign('help_url', $this->gen_help_url('customer_edit'));

        $balance = CustomerTransaction::getBalance($customer_id);
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

    private function getTabs(int $customer_id, $active = '')
    {
        $this->data['tabs']['general'] = [
            'href'       => $this->html->getSecureURL('sale/customer/update', '&customer_id='.$customer_id),
            'text'       => $this->language->get('tab_customer_details'),
            'active'     => ($active === 'general'),
            'sort_order' => 0,
        ];
        if (H::has_value($customer_id)) {
            $this->data['tabs']['transactions'] = [
                'href'       => $this->html->getSecureURL('sale/customer_transaction', '&customer_id='.$customer_id),
                'text'       => $this->language->get('tab_transactions'),
                'active'     => ($active === 'transactions'),
                'sort_order' => 10,
            ];
            $this->data['tabs']['notes'] = [
                'href'       => $this->html->getSecureURL('sale/customer/notes', '&customer_id='.$customer_id),
                'text'       => $this->language->get('tab_customer_notes'),
                'active'     => ($active === 'notes'),
                'sort_order' => 20,
            ];
            if ($this->config->get('config_save_customer_communication')) {
                $this->data['tabs']['communications'] = [
                    'href'       => $this->html->getSecureURL('sale/customer/communications',
                        '&customer_id='.$customer_id),
                    'text'       => $this->language->get('tab_customer_communications'),
                    'active'     => ($active === 'communications'),
                    'sort_order' => 30,
                ];
            }
        }

        $obj = $this->dispatch('responses/common/tabs', [
                'sale/customer',
                //parent controller. Use customer to use for other extensions that will add tabs via their hooks
                ['tabs' => $this->data['tabs']],
            ]
        );
        $this->data['tabs'] = $obj->dispatchGetOutput();
    }

    public function insert_address()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->document->setTitle($this->language->get('heading_title'));

        $customer_id = $this->request->get['customer_id'];
        if ($this->request->is_POST() && $this->validateAddressForm()) {
            $data = $this->request->post;
            $data['customer_id'] = $customer_id;
            $address = new Address($data);
            $address->save();

            $address_id = $address->address_id;
            $redirect_url = $this->html->getSecureURL(
                'sale/customer/update',
                '&customer_id='.$customer_id.'&address_id='.$address_id
            );

            //do we need to update default address?
            if ($this->request->post['default']) {
                Customer::find($customer_id)->update(['address_id' => $address_id]);
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
                Customer::find($customer_id)->update(['address_id' => $address_id]);
            }

            $address = Address::find($address_id);
            $data = $this->request->post;
            $data['customer_id'] = $customer_id;
            $address->update($data);

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
            $customer_info = Customer::find($customer_id);
            if($customer_info) {
                $customer_info['orders_count'] = Order::where('customer_id', '=', $customer_id)->where('order_status_id', '>', 0)->get()->count();
            }
            $this->data['button_orders_count'] = $this->html->buildElement(
                [
                    'type'  => 'button',
                    'name'  => 'view orders',
                    'text'  => $this->language->get('text_total_order').' '.(int)$customer_info['orders_count'],
                    'style' => 'button2',
                    'href'  => $this->html->getSecureURL('sale/order', '&customer_id='.$customer_id),
                    'title' => $this->language->get('text_view').' '.$this->language->get('tab_history'),
                ]
            );
            $this->data['message'] = $this->html->buildElement([
                'type'   => 'button',
                'text'   => $this->language->get('button_message'),
                'href'   => $this->html->getSecureURL('sale/contact', '&to[]='.$customer_id),
                'target' => 'new',
            ]);
            $this->data['new_order'] = $this->html->buildElement([
                'type'   => 'button',
                'text'   => $this->language->get('text_create_order'),
                'href'   => $this->html->getSecureURL('sale/order/createOrder', '&customer_id='.$customer_id),
                'target' => 'new',
            ]);
            $this->data['addresses'] = Address::getAddressesByCustomerId($customer_id);
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

        $this->getTabs($customer_id, 'general');

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
        $balance = CustomerTransaction::getBalance($customer_id);
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
        /**
         * @var Customer $customer
         */
        $customer = Customer::find($customer_id);
        H::event('admin\sendApprovalEmail', [new ABaseEvent($customer->toArray())]);
        $customer->update(['approved' => 1]);


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
                    ['customer_id' => $this->request->get['customer_id'],
                      'actoronbehalf' => $this->user->getId()]);
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
            $customer_info = Customer::find($customer_id);
            if ($customer_info['address_id'] == $address_id) {
                $this->error['warning'] = $this->language->get('error_delete_default');
                $this->getAddressForm();
            } else {
                Address::destroy($address_id);
                $this->session->data['success'] = $this->language->get('text_success');
                abc_redirect($this->html->getSecureURL('sale/customer/update', '&customer_id='.$customer_id));
            }
        }
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    /**
     * @param array $data
     * @param null $customer_id
     *
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     */
    protected function validateForm(array $data, $customer_id = null)
    {
        if (!$this->user->canModify('sale/customer')) {
            $this->error['warning'] = $this->language->get('error_permission');
            return false;
        }

        if (!$this->csrftoken->isTokenValid()) {
            $this->error['warning'] = $this->language->get('error_unknown');
            return false;
        }

        if($customer_id){
           $data['customer_id'] = $customer_id;
           $customer = Customer::find($customer_id);
           if(!$data['password'] && !$data['password_confirmation']){
               unset($data['password'],$data['password_confirmation']);
           }
        }else{
            $customer = new Customer();
        }

        try{
            $customer->validate($data);
        }catch(ValidationException $e){
            H::SimplifyValidationErrors($customer->errors()['validation'], $this->error);
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
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
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

        if (mb_strlen($this->request->post['company']) > 32) {
            $this->error['company'] = $this->language->get('error_company');
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

    public function notes()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->document->setTitle($this->language->get('heading_title'));

        if ($this->request->is_POST()) {
            $data = [
                'user_id'     => $this->user->getId(),
                'customer_id' => $this->request->get['customer_id'],
            ];
            $data = array_merge($data, $this->request->post);
            if (CustomerNotes::create($data)->save()) {
                $this->session->data['success'] = $this->language->get('text_success');
            }
            abc_redirect($this->html->getSecureURL('sale/customer/notes',
                '&customer_id='.$this->request->get['customer_id']));
        }

        if (isset($this->request->get['customer_id'])) {
            $customer_id = (int)$this->request->get['customer_id'];
        } else {
            $customer_id = 0;
        }

        $customer_info = Customer::find($customer_id);

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
        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('sale/customer/update', '&customer_id='.$customer_id),
            'text'      => $this->language->get('text_edit').' '.$this->language->get('text_customer').' - '
                .$customer_info['firstname'].' '.$customer_info['lastname'],
            'separator' => ' :: ',
        ]);
        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('sale/customer_notes', '&customer_id='.$customer_id),
            'text'      => $this->language->get('heading_title_notes'),
            'separator' => ' :: ',
            'current'   => true,
        ]);

        $this->getTabs($customer_id, 'notes');

        if (isset($this->session->data['success'])) {
            $this->data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $this->data['success'] = '';
        }

        $notes = CustomerNotes::getNotes($customer_id);
        $this->data['notes'] = [];
        foreach ($notes as &$note) {
            $note->note_added = H::dateISO2Display(
                $note->note_added,
                $this->language->get('date_format_short').' '.$this->language->get('time_format')
            );
            $this->data['notes'][] = $note;
        }

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

    public function communications()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        if (!$this->config->get('config_save_customer_communication')) {
            abc_redirect($this->html->getSecureURL('sale/customer'));
        }

        $this->document->setTitle($this->language->get('heading_title'));

        if (isset($this->request->get['customer_id'])) {
            $customer_id = (int)$this->request->get['customer_id'];
        } else {
            $customer_id = 0;
        }

        $customer_info = Customer::find($customer_id);

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
        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('sale/customer/update', '&customer_id='.$customer_id),
            'text'      => $this->language->get('text_edit').' '.$this->language->get('text_customer').' - '
                .$customer_info['firstname'].' '.$customer_info['lastname'],
            'separator' => ' :: ',
        ]);
        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('sale/customer/communications', '&customer_id='.$customer_id),
            'text'      => $this->language->get('heading_title_communications'),
            'separator' => ' :: ',
            'current'   => true,
        ]);

        $this->getTabs($customer_id, 'communications');

        if (isset($this->session->data['success'])) {
            $this->data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $this->data['success'] = '';
        }

        $grid_settings = [
            //id of grid
            'table_id'       => 'report_purchased_grid',
            // url to load data from
            'url'            => $this->html->getSecureURL('listing_grid/customer_communications',
                '&customer_id='.$customer_id),
            // default sort column
            'sortname'       => 'date_added',
            'columns_search' => true,
            'multiselect'    => 'false',
            'search_form'    => false,
            'actions'        => [
                'view' => [
                    'text' => $this->language->get('text_view'),
                    'href' => $this->html->getSecureURL('listing_grid/customer_communications/communication_info',
                        '&customer_id='.$customer_id.'&id=%ID%'),
                ],
            ],
            'grid_ready'     => 'grid_ready();',
        ];

        $grid_settings['colNames'] = [
            $this->language->get('column_subject'),
            $this->language->get('column_type'),
            $this->language->get('column_status'),
            $this->language->get('column_date_added'),
            $this->language->get('column_user'),
        ];

        $grid_settings['colModel'] = [
            [
                'name'     => 'subject',
                'index'    => 'subject',
                'width'    => 200,
                'align'    => 'left',
                'sortable' => true,
                'search'   => false,
            ],
            [
                'name'     => 'type',
                'index'    => 'type',
                'width'    => 50,
                'align'    => 'center',
                'sortable' => true,
                'search'   => false,
            ],
            [
                'name'     => 'status',
                'index'    => 'status',
                'width'    => 50,
                'align'    => 'left',
                'sortable' => true,
                'search'   => false,
            ],
            [
                'name'     => 'date_added',
                'index'    => 'date_added',
                'width'    => 80,
                'align'    => 'left',
                'sortable' => true,
                'search'   => false,
            ],
            [
                'name'     => 'user',
                'index'    => 'user',
                'width'    => 90,
                'align'    => 'center',
                'sortable' => false,
                'search'   => false,
            ],
        ];

        $grid = $this->dispatch('common/listing_grid', [$grid_settings]);
        $this->view->assign('listing_grid', $grid->dispatchGetOutput());

        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/sale/customer_communications.tpl');

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

    }

}
