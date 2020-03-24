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

namespace abc\controllers\storefront;

use abc\core\ABC;
use abc\core\engine\AController;
use abc\core\engine\AForm;
use abc\core\lib\AEncryption;
use abc\core\lib\AException;
use abc\models\customer\Address;
use abc\models\customer\Customer;
use abc\modules\events\ABaseEvent;
use H;
use Illuminate\Database\Eloquent\Collection;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;

class ControllerPagesAccountLogin extends AController
{
    public $error = [];
    public $data = [];

    public function main()
    {
        //do redirect to secure page when ssl is enabled
        if ($this->config->get('config_ssl') && $this->config->get('config_ssl_url') && !ABC::env('HTTPS')) {
            abc_redirect($this->html->getSecureURL('account/login'));
        }

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        if ($this->customer->isLogged()) {
            abc_redirect($this->html->getSecureURL('account/account'));
        }

        if (isset($this->session->data['actoronbehalf'])) {
            unset($this->session->data['actoronbehalf']);
        }

        $this->document->setTitle($this->language->get('heading_title'));
        $loginname = '';
        if ($this->request->is_POST()) {
            if (!$this->csrftoken->isTokenValid()) {
                $this->error['message'] = $this->language->get('error_unknown');
            } else {
                if (isset($this->request->post['account'])) {
                    $this->session->data['account'] = $this->request->post['account'];

                    if ($this->request->post['account'] == 'register') {
                        abc_redirect($this->html->getSecureURL('account/create'));
                    }

                    if ($this->request->post['account'] == 'guest') {
                        abc_redirect($this->html->getSecureURL('checkout/guest_step_1'));
                    }
                }
                //support old email based login
                $loginname = (isset($this->request->post['loginname']))
                    ? $this->request->post['loginname']
                    : $this->request->post['email'];
                $password = $this->request->post['password'];
                if (isset($loginname) && isset($password) && $this->_validate($loginname, $password)) {
                    unset(
                        $this->session->data['guest'],
                        $this->session->data['account']
                    );

                    $address_id = $this->customer->getAddressId();
                    $address = Address::find($address_id);
                    $address = $address ? $address->toArray() : [];

                    $this->tax->setZone($address['country_id'], $address['zone_id']);

                    if ($this->session->data['redirect']) {
                        $redirect_url = $this->session->data['redirect'];
                        unset($this->session->data['redirect']);
                    } else {
                        $redirect_url = $this->html->getSecureURL('account/account');
                    }
                    $this->extensions->hk_ProcessData($this);
                    abc_redirect($redirect_url);
                }
            }
        } elseif (H::has_value($this->request->get['ac'])) {
            //activation of account via email-code.
            /**
             * @var AEncryption $enc
             */
            $enc = ABC::getObjectByAlias('AEncryption', [$this->config->get('encryption_key')]);
            list($customer_id, $activation_code) = explode("::", $enc->decrypt((string)$this->request->get['ac']));
            if ($customer_id && $activation_code) {
                //get customer
                $customer = Customer::find((int)$customer_id);
                if ($customer) {
                    $customer_info = $customer->toArray();
                    //if activation code presents in data and matching
                    if ($activation_code == $customer_info['data']['email_activation']) {
                        unset($customer_info['data']['email_activation']);
                        if (!$customer_info['status']) {
                            //activate
                            //and update data and remove email_activation code
                            $customer->update(
                                [
                                    'status' => 1,
                                    'data'   => $customer_info['data'],
                                ]
                            );
                            //send welcome email
                            $customer_info['activated'] = true;
                            H::event('storefront\sendWelcomeEmail', [new ABaseEvent($customer_info)]);

                            $this->session->data['success'] = $this->language->get('text_success_activated');
                        } else {
                            //update data and remove email_activation code
                            $customer->update(['data' => $customer_info['data']]);
                            $this->session->data['success'] = $this->language->get('text_already_activated');
                        }
                    } elseif (!$customer_info['data']['email_activation'] && $customer_info['status']) {
                        $this->session->data['success'] = $this->language->get('text_already_activated');
                    }
                }
            }
        }

        $this->document->resetBreadcrumbs();

        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getHomeURL(),
                'text'      => $this->language->get('text_home'),
                'separator' => false,
            ]);

        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSecureURL('account/account'),
                'text'      => $this->language->get('text_account'),
                'separator' => $this->language->get('text_separator'),
            ]);

        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSecureURL('account/login'),
                'text'      => $this->language->get('text_login', 'account/login'),
                'separator' => $this->language->get('text_separator'),
            ]);

        $this->view->assign('error', '');
        if (isset($this->error['message'])) {
            $this->view->assign('error', $this->error['message']);
        }

        $form = new AForm();
        $form->setForm(['form_name' => 'accountFrm']);
        $this->data['form1']['form_open'] = $form->getFieldHtml(
            [
                'type'   => 'form',
                'name'   => 'accountFrm',
                'action' => $this->html->getSecureURL('account/login'),
                'csrf'   => true,
            ]
        );

        $this->data['form1']['register'] = $form->getFieldHtml(
            [
                'type'    => 'radio',
                'id'      => 'account',
                'name'    => 'account',
                'options' => [
                    'register' => $this->language->get('text_account'),
                ],
                'value'   => (isset($this->session->data['account']) ? $this->session->data['account'] : 'register'),
            ]
        );
        $this->data['form1']['guest'] = $form->getFieldHtml(
            [
                'type'    => 'radio',
                'id'      => 'account',
                'name'    => 'account',
                'options' => [
                    'guest' => $this->language->get('text_guest'),
                ],
                'value'   => ($this->session->data['account'] == 'guest' ? 'guest' : ''),
            ]
        );
        $this->data['form1']['continue'] = $form->getFieldHtml(
            [
                'type' => 'submit',
                'name' => $this->language->get('button_continue'),
                'icon' => 'fa fa-check',
            ]);

        //second form
        $form = new AForm();
        $form->setForm(['form_name' => 'loginFrm']);
        $this->data['form2']['form_open'] = $form->getFieldHtml(
            [
                'type'   => 'form',
                'name'   => 'loginFrm',
                'action' => $this->html->getSecureURL('account/login'),
                'csrf'   => true,
            ]
        );

        if ($this->config->get('prevent_email_as_login')) {
            $this->data['noemaillogin'] = true;
        }

        $this->data['form2']['loginname'] = $form->getFieldHtml(
            [
                'type'  => 'input',
                'name'  => 'loginname',
                'value' => $loginname,
            ]);
        //support old email based logging. Remove in the future
        $this->data['form2']['email'] = $form->getFieldHtml(
            [
                'type'  => 'input',
                'name'  => 'email',
                'value' => $loginname,
            ]);
        $this->data['form2']['password'] = $form->getFieldHtml(
            [
                'type' => 'password',
                'name' => 'password',
            ]);
        $this->data['form2']['login_submit'] = $form->getFieldHtml(
            [
                'type' => 'submit',
                'name' => $this->language->get('button_login'),
                'icon' => 'fa fa-lock',
            ]);

        $this->view->assign('success', '');
        if (isset($this->session->data['success'])) {
            $this->view->assign('success', $this->session->data['success']);
            unset($this->session->data['success']);
        }

        $this->data['forgotten_pass'] = $this->html->getSecureURL('account/forgotten/password');
        $this->data['forgotten_login'] = $this->html->getSecureURL('account/forgotten/loginname');
        $this->data['guest_checkout'] =
            ($this->config->get('config_guest_checkout') && $this->cart->hasProducts() && !$this->cart->hasDownload());

        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/account/login.tpl');

        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    /**
     * @param string $loginname
     * @param string $password
     *
     * @return bool
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws AException
     */
    private function _validate($loginname, $password)
    {

        if ($this->customer->login($loginname, $password) !== true) {
            if ($this->config->get('config_customer_email_activation')) {
                //check if account is not confirmed in the email.
                $customer_info = Customer::search(
                    [
                        'filter' =>
                            [
                                'search_operator'                                                      => 'equal',
                                //if email as login not allowed - seek by login
                                ($this->config->get('prevent_email_as_login') ? 'loginname' : 'email') => $loginname,
                            ],
                    ]
                );

                if ($customer_info) {
                    /** @var Collection $customer_info */
                    $customer_info = $customer_info->first();
                    if ($customer_info) {
                        $customer_info = $customer_info->toArray();
                    }
                }

                if ($customer_info
                    && !$customer_info['status']
                    && isset($customer_info['data']['email_activation'])
                    && $customer_info['data']['email_activation']) {
                    //show link for resend activation code to email
                    /**
                     * @var AEncryption $enc
                     */
                    $enc = ABC::getObjectByAlias('AEncryption', [$this->config->get('encryption_key')]);
                    $rid = $enc->encrypt($customer_info['customer_id'].'::'.$customer_info['data']['email_activation']);
                    $this->error['message'] .= sprintf($this->language->get('text_resend_activation_email'),
                        "\n".$this->html->getSecureURL('account/create/resend', '&rid='.$rid)
                    );

                    return false;
                }
            }
            $this->error['message'] .= $this->language->get('error_login');

        } else {
            $address = [];
            $addressModel = Address::find($this->customer->getAddressId());
            if ($addressModel) {
                $address = $addressModel->toArray();
            }

            $this->session->data['country_id'] = $address['country_id'];
            $this->session->data['zone_id'] = $address['zone_id'];

            //check if existing customer has loginname = email. Redirect if not allowed
            if ($this->config->get('prevent_email_as_login') && $this->customer->isLoginnameAsEmail()) {
                abc_redirect($this->html->getSecureURL('account/edit'));
            }
        }

        $this->extensions->hk_ValidateData($this);

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }
}
