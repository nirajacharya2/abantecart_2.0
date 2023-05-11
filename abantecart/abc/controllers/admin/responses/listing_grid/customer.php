<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2022 Belavier Commerce LLC

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
use abc\core\engine\ALanguage;
use abc\core\engine\Registry;
use abc\core\lib\AEncryption;
use abc\core\lib\AError;
use abc\core\lib\AException;
use abc\core\lib\AJson;
use abc\core\lib\AMail;
use abc\models\admin\ModelSettingStore;
use abc\models\customer\Address;
use abc\models\customer\Customer;
use abc\models\order\Order;
use abc\modules\events\ABaseEvent;
use H;
use Illuminate\Validation\ValidationException;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;
use stdClass;

class ControllerResponsesListingGridCustomer extends AController
{
    public $error = '';

    public function main()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('sale/customer');
        $this->load->library('json');

        $approved = [
            1 => $this->language->get('text_yes'),
            0 => $this->language->get('text_no'),
        ];

        $page = $this->request->post['page'];  // get the requested page
        $limit = $this->request->post['rows']; // get how many rows we want to have into the grid
        $sidx = $this->request->post['sidx'];  // get index row - i.e. user click to sort
        $sord = $this->request->post['sord'];  // get the direction

        $data = [
            'sort'  => $sidx,
            'order' => $sord,
            'start' => ($page - 1) * $limit,
            'limit' => $limit,
        ];
        if (H::has_value($this->request->get['customer_group'])) {
            $data['filter']['customer_group_id'] = $this->request->get['customer_group'];
        }
        if (H::has_value($this->request->get['status'])) {
            $data['filter']['status'] = $this->request->get['status'];
        }
        if (H::has_value($this->request->get['approved'])) {
            $data['filter']['approved'] = $this->request->get['approved'];
        }

        $allowedFields = array_merge(['name', 'email'], (array)$this->data['allowed_fields']);

        if ($this->request->post['_search'] === 'true') {
            $searchData = AJson::decode(htmlspecialchars_decode($this->request->post['filters']), true);

            foreach ($searchData['rules'] as $rule) {
                if (!in_array($rule['field'], $allowedFields)) {
                    continue;
                }
                $data['filter'][$rule['field']] = $rule['data'];
            }
        }

        $total = Customer::getTotalCustomers($data);
        if ($total > 0) {
            $total_pages = ceil($total / $limit);
        } else {
            $total_pages = 0;
        }

        if ($page > $total_pages) {
            $page = $total_pages;
            $data['start'] = ($page - 1) * $limit;
        }

        $response = new stdClass();
        $response->page = $page;
        $response->total = $total_pages;
        $response->records = $total;
        $orders_count = 0;

        if ($sidx == 'orders_count') {
            $data['mode'] = 'default';
        } else {
            $data['mode'] = 'quick';
        }

        $results = Customer::search($data);
        //push result into public scope to get access from extensions
        $this->data['results'] = $results;

        if ($data['mode'] == 'quick') {
            //get orders count for customers list by separate request to prevent slow sql issue
            $customers_ids = [];
            foreach ($results as $result) {
                $customers_ids[] = $result['customer_id'];
            }
            $orders_count = Order::getCountOrdersByCustomerIds($customers_ids);
        }
        $i = 0;
        foreach ($results as $result) {
            if ($data['mode'] == 'quick') {
                $order_cnt = (int)$orders_count[$result['customer_id']];
            } else {
                $order_cnt = (int)$result['orders_count'];
            }
            $response->rows[$i]['id'] = $result['customer_id'];
            $response->rows[$i]['cell'] = [
                $result['name'],
                '<a href="'
                . $this->html->getSecureURL('sale/contact', '&email[]=' . $result['email']) . '">'
                . $result['email'] . '</a>',
                $result['customer_group'],
                $this->html->buildCheckbox(
                    [
                        'name'  => 'status[' . $result['customer_id'] . ']',
                        'value' => $result['status'],
                        'style' => 'btn_switch',
                    ]
                ),
                $this->html->buildSelectBox(
                    [
                        'name'    => 'approved[' . $result['customer_id'] . ']',
                        'value'   => $result['approved'],
                        'options' => $approved,
                    ]
                ),
                ($order_cnt > 0 ?
                    $this->html->buildButton(
                        [
                            'name'   => 'view orders',
                            'text'   => $order_cnt,
                            'style'  => 'btn btn-default btn-xs',
                            'href'   => $this->html->getSecureURL('sale/order', '&customer_id=' . $result['customer_id']),
                            'title'  => $this->language->get('text_view') . ' ' . $this->language->get('tab_history'),
                            'target' => '_blank',
                        ]
                    )
                    : 0),
            ];
            $i++;
        }
        $this->data['response'] = $response;

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
        $this->load->library('json');
        $this->response->addJSONHeader();
        $this->response->setOutput(AJson::encode($this->data['response']));
    }

    public function update()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('sale/customer');
        if (!$this->user->canModify('listing_grid/customer')) {
            $error = new AError('');
            $error->toJSONResponse('NO_PERMISSIONS_403',
                [
                    'error_text'  => sprintf(
                        $this->language->get('error_permission_modify'),
                        'listing_grid/customer'
                    ),
                    'reset_value' => true,
                ]
            );
            return;
        }

        switch ($this->request->post['oper']) {
            case 'del':
                $ids = explode(',', $this->request->post['id']);
                if (!empty($ids)) {
                    Customer::whereIn('customer_id', $ids)->delete();
                    Registry::cache()->flush('customer');
                }
                break;
            case 'save':
                $ids = explode(',', $this->request->post['id']);
                $ids = array_unique($ids);
                if (!empty($ids)) {
                    foreach ($ids as $id) {
                        $err = $this->validateForm('status', $this->request->post['status'][$id], $id);
                        $customer = Customer::find($id);
                        if ($customer && !$err) {
                            $customer->update(['status' => $this->request->post['status'][$id]]);
                        } else {
                            $error = new AError('');
                            $error->toJSONResponse(
                                'VALIDATION_ERROR_406',
                                [
                                    'error_text'  => $err,
                                    'reset_value' => false,
                                ]
                            );
                            return;
                        }
                        $do_approve = $this->request->post['approved'][$id];
                        $err = $this->validateForm('approved', $do_approve, $id);
                        if (!$err) {
                            //if customer is not subscriber - send email
                            if ($do_approve && !$customer->isSubscriber()) {
                                //send email when customer was not approved
                                H::event('admin\sendApprovalEmail', [new ABaseEvent($customer->toArray())]);
                            }
                            //do not change order of calls here!!!
                            $customer->update(['approved' => $do_approve]);
                        } else {
                            $error = new AError('');
                            $error->toJSONResponse('VALIDATION_ERROR_406',
                                [
                                    'error_text'  => $err,
                                    'reset_value' => false,
                                ]
                            );
                            return;
                        }
                    }
                    Registry::cache()->flush('customer');
                }
                break;
            default:
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    /**
     * update only one field
     *
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws AException
     */
    public function update_field()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('sale/customer');

        if (!$this->user->canModify('listing_grid/customer')) {
            $error = new AError('');
            $error->toJSONResponse('NO_PERMISSIONS_403',
                [
                    'error_text'  => sprintf(
                        $this->language->get('error_permission_modify'),
                        'listing_grid/customer'
                    ),
                    'reset_value' => true,
                ]
            );
            return;

        }
        $customer_id = $this->request->get['id'];
        $address_id = $this->request->get['address_id'];
        $post = $this->request->post;
        if ($customer_id) {
            if ($post['password'] || $post['password_confirm']) {
                $error = new AError('');
                if (mb_strlen($post['password']) < 4) {
                    $error->toJSONResponse('VALIDATION_ERROR_406',
                        [
                            'error_text'  => $this->language->get('error_password'),
                            'reset_value' => true,
                        ]
                    );
                    return;
                }
                if ($post['password'] != $post['password_confirmation']) {
                    $error->toJSONResponse('VALIDATION_ERROR_406',
                        [
                            'error_text'  => $this->language->get('error_confirm'),
                            'reset_value' => true,
                        ]
                    );
                    return;
                }
                //passwords do match, save
                $customer = Customer::find($customer_id);
                $customer?->update(
                    [
                        'password'              => $post['password'],
                        'password_confirmation' => $post['password_confirmation'],
                    ]
                );

            } else {
                foreach ($post as $field => $value) {
                    $err = $this->validateForm($field, $value, $customer_id);
                    if (!$err) {
                        $customer = Customer::find($customer_id);
                        if ($field == 'approved') {
                            //send email when customer was not approved
                            if ($value && !$customer->isSubscriber()) {
                                H::event('admin\sendApprovalEmail', [new ABaseEvent($customer->toArray())]);
                            }
                        }
                        if ($field == 'default' && $address_id) {
                            //set default address
                            $customer->update(['address_id' => $address_id]);
                        } else {
                            if ($address_id) {
                                $address = Address::find($address_id);
                                $address->update([$field => $value]);
                            } else {
                                $customer->update([$field => $value]);
                            }
                        }
                    } else {
                        $error = new AError('');
                        $error->toJSONResponse('VALIDATION_ERROR_406',
                            [
                                'error_text'  => $err,
                                'reset_value' => false,
                            ]
                        );
                        return;
                    }
                }
            }
            //update controller data
            $this->extensions->hk_UpdateData($this, __FUNCTION__);
            return;
        }

        //request sent from jGrid. ID is key of array
        foreach ($this->request->post as $field => $value) {
            foreach ($value as $k => $v) {
                $err = $this->validateForm($field, $v);
                if (!$err) {
                    $customer = Customer::find($k);
                    if ($field == 'approved') {
                        if ($v && !$customer->isSubscriber()) {
                            //send email when customer was not approved
                            H::event('admin\sendApprovalEmail', [new ABaseEvent($customer->toArray())]);
                        }
                    }
                    $customer->update([$field => $v]);
                } else {
                    $error = new AError('');
                    $error->toJSONResponse('VALIDATION_ERROR_406',
                        [
                            'error_text'  => $err,
                            'reset_value' => false,
                        ]
                    );
                    return;
                }
            }
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    protected function validateForm($field, $value, $customer_id = '')
    {
        $this->error = '';
        $errors = [];
        $customer = new Customer();
        try {
            $data = [$field => $value];
            if ($customer_id) {
                $data['customer_id'] = $customer_id;
            }
            $customer->validate($data);
        } catch (ValidationException $e) {
            H::SimplifyValidationErrors($customer->errors()['validation'], $errors);
            if ($errors) {
                $this->error = current($errors);
            }
        }
        if (!$this->error) {
            switch ($field) {
                case 'loginname' :
                    $login_name_pattern = '/^[\w._-]+$/i';
                    $value = preg_replace('/\s+/', '', $value);
                    if (!preg_match($login_name_pattern, $value) && $this->config->get('prevent_email_as_login')) {
                        $this->error = $this->language->get('error_loginname');
                        //check uniqueness of loginname
                    } else {
                        if (!Customer::isUniqueLoginname($value, $customer_id)) {
                            $this->error = $this->language->get('error_loginname_notunique');
                        }
                    }
                    break;
                case 'email':
                    if (!preg_match(ABC::env('EMAIL_REGEX_PATTERN'), $value)) {
                        $this->error = $this->language->get('error_email');
                    }//check unique email
                    else {
                        $exists = Customer::search(['filter' => ['email' => $value]])->toArray();
                        if ($exists) {
                            foreach ($exists as $details) {
                                if ($details['customer_id'] != $customer_id) {
                                    $this->error = $this->language->get('error_email_exists');
                                    break;
                                }
                            }
                        }
                    }
                    break;
            }
        }
        if (!$this->error) {
            $address = new Address();
            try {
                $data = [$field => $value];
                if ($customer_id) {
                    $data['customer_id'] = $customer_id;
                }
                $address->validate($data);
            } catch (ValidationException $e) {
                H::SimplifyValidationErrors($address->errors()['validation'], $errors);
                if ($errors) {
                    $this->error = current($errors);
                }
            }
        }

        $this->extensions->hk_ValidateData($this);

        return $this->error;
    }

    public function customers()
    {
        $customers_data = [];
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        /** @var ModelSettingStore $mdl */
        $mdl = $this->load->model('setting/store');

        if ($this->request->post['term']) {
            $filter = [
                'limit'               => 20,
                'content_language_id' => $this->language->getContentLanguageID(),
                'filter'              => [
                    'name_email'     => $this->request->post['term'],
                    'match'          => 'any',
                    'only_customers' => 1,
                    'exclude'        => (array)$this->request->post['exclude'],
                ],
            ];
            if (H::has_value($this->session->data['current_store_id'])) {
                $filter['store_id'] = (int)$this->session->data['current_store_id'];
            }

            if (!$filter['store_id'] && !$mdl->isDefaultStore()) {
                $filter['store_id'] = $this->config->get('config_store_id');
            }

            $customers = Customer::search($filter)->toArray();
            foreach ($customers as $cdata) {
                $customers_data[] = [
                    'id'   => $cdata['customer_id'],
                    'name' => $cdata['firstname'] . ' ' . $cdata['lastname'],
                ];
            }
        }

        $this->data['customers_data'] = $customers_data;
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->load->library('json');
        $this->response->addJSONHeader();
        $this->response->setOutput(AJson::encode($this->data['customers_data']));
    }

    public function resetPassword()
    {
        $customer_id = (int)$this->request->get['customer_id'];
        $this->loadLanguage('sale/customer');

        if (!$this->user->canModify('sale/customer')) {
            $error = new AError('');
            $error->toJSONResponse('VALIDATION_ERROR_406',
                [
                    'error_text'  => sprintf(
                        $this->language->get('error_permission_modify'),
                        'sale/customer'
                    ),
                    'reset_value' => false,
                ]
            );
            return;
        }
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $customer_info = Customer::find($customer_id);

        $error_text = $this->validateBeforePasswordReset($customer_info);
        if ($error_text) {
            $error = new AError('');
            $error->toJSONResponse(
                'VALIDATION_ERROR_406',
                [
                    'error_text'  => $error_text,
                    'reset_value' => false,
                ]
            );
            return;
        }

        $code = H::genToken();
        //save password reset code
        $data = $customer_info->data;
        $data['password_reset'] = $code;
        Customer::find($customer_id)->update(['data' => $data]);
        //build reset link
        /**
         * @var AEncryption $enc
         */
        $enc = ABC::getObjectByAlias('AEncryption', [$this->config->get('encryption_key')]);
        $rToken = $enc->encrypt($customer_id . '::' . $code);

        $link = $this->html->getSecureURL('account/forgotten/reset', '&rtoken=' . $rToken, null, 'storefront');

        $language = new ALanguage($this->registry, $this->language->getLanguageCode(), 0);
        $language->load('mail/account_forgotten');

        $subject = sprintf($language->get('text_subject'), $this->config->get('store_name'));
        $message = sprintf($this->language->get('text_password_was_reset'), $this->config->get('store_name')) . "\n\n";
        $message .= $language->get('text_password') . "\n\n";
        $message .= $link;

        $mail = new AMail($this->config);
        $mail->setTo($customer_info['email']);
        $mail->setFrom($this->config->get('store_main_email'));
        $mail->setSender($this->config->get('store_name'));
        $mail->setSubject($subject);
        $mail->setText(html_entity_decode($message, ENT_QUOTES, ABC::env('APP_CHARSET')));
        //$arUser = H::recognizeUser();
        //$user = User::find($arUser['user_id']);
        if ($this->user) {
            $mail->setUser($this->user);
        }
        $result = $mail->send();
        if (!$result) {
            $error = new AError('');
            $error->toJSONResponse(
                'VALIDATION_ERROR_406',
                [
                    'error_text'  => $this->language->get('error_reset_link_not_sent'),
                    'reset_value' => false,
                ]
            );
            return;
        } else {
            $this->extensions->hk_UpdateData($this, __FUNCTION__);
        }
        $this->load->library('json');
        $this->response->addJSONHeader();
        $this->response->setOutput(AJson::encode(
            [
                'result'  => true,
                'success' => $this->language->get('text_password_reset_success'),
            ]
        )
        );
    }

    protected function validateBeforePasswordReset($customer_info)
    {
        $this->error = '';
        if (!$customer_info) {
            $this->error = $this->language->get('error_unknown_customer');
        } elseif (!$customer_info['email']) {
            $this->error = $this->language->get('error_no_email');
        } elseif (!$customer_info['status'] || !$customer_info['approved']) {
            $this->error = $this->language->get('error_disabled_customer');
        }

        $this->extensions->hk_ValidateData($this, __FUNCTION__, $customer_info);

        return $this->error;
    }
}
