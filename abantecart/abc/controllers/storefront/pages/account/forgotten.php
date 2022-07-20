<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2017 Belavier Commerce LLC

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
use abc\models\customer\Customer;
use abc\modules\events\ABaseEvent;
use H;
use Illuminate\Validation\ValidationException;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;

class ControllerPagesAccountForgotten extends AController
{
    private $error = [];
    public $data = [];

    public function main()
    {
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->password();
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function password()
    {

        $this->extensions->hk_InitData($this, __FUNCTION__);

        if ($this->customer->isLogged()) {
            abc_redirect($this->html->getSecureURL('account/account'));
        }

        $this->document->setTitle($this->language->get('heading_title'));

        if ($this->request->is_POST()) {
            if (!$this->csrftoken->isTokenValid()) {
                $this->error['message'] = $this->language->get('error_unknown');
                return false;
            }
            $customer = $this->findCustomer('password', $this->request->post);
            if ($customer) {
                $code = H::genToken(32);
                $data = $customer->data;
                $data['password_reset'] = $code;
                //save password reset code
                $customer->update(['data' => $data]);
                //build reset link
                /**
                 * @var AEncryption $enc
                 */
                $enc = ABC::getObjectByAlias('AEncryption', [$this->config->get('encryption_key')]);
                $rtoken = $enc->encrypt($customer->customer_id.'::'.$code);

                H::event(
                    'storefront\sendPasswordResetLinkEmail',
                    [
                        new ABaseEvent(
                            $customer->toArray(),
                            ['rtoken' => $rtoken]),
                    ]
                );

                $this->session->data['success'] = $this->language->get('text_success');
                abc_redirect($this->html->getSecureURL('account/login'));
            }
        }

        $this->document->resetBreadcrumbs();

        $this->document->addBreadcrumb([
            'href'      => $this->html->getHomeURL(),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);

        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('account/account'),
            'text'      => $this->language->get('text_account'),
            'separator' => $this->language->get('text_separator'),
        ]);

        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('account/forgotten/password'),
            'text'      => $this->language->get('text_forgotten'),
            'separator' => $this->language->get('text_separator'),
        ]);

        $this->view->assign('error', $this->error['message']);
        $this->view->assign('action', $this->html->getSecureURL('account/forgotten'));
        $this->view->assign('back', $this->html->getSecureURL('account/account'));

        $form = new AForm();
        $form->setForm(['form_name' => 'forgottenFrm']);
        $this->data['form']['form_open'] = $form->getFieldHtml(
            [
                'type'   => 'form',
                'name'   => 'forgottenFrm',
                'action' => $this->html->getSecureURL('account/forgotten/password'),
                'csrf'   => true,
            ]
        );

        //verify loginname if non email login used or data encryption is ON
        if ($this->config->get('prevent_email_as_login') || $this->dcrypt->active) {
            $this->data['form']['fields']['loginname'] = $form->getFieldHtml([
                'type'  => 'input',
                'name'  => 'loginname',
                'value' => $this->request->post['loginname'],
            ]);
            $this->data['help_text'] = $this->language->get('text_loginname_email');
        } else {
            $this->data['help_text'] = $this->language->get('text_email');
        }

        $this->data['form']['fields']['email'] = $form->getFieldHtml([
            'type'  => 'input',
            'name'  => 'email',
            'value' => $this->request->post['email'],
        ]);

        $this->data['form']['continue'] = $form->getFieldHtml([
            'type' => 'submit',
            'name' => $this->language->get('button_continue'),
        ]);
        $this->data['form']['back'] = $form->getFieldHtml([
            'type'  => 'button',
            'name'  => 'back',
            'style' => 'button',
            'text'  => $this->language->get('button_back'),
        ]);
        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/account/forgotten.tpl');

        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function reset()
    {

        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('mail/account_forgotten');

        if ($this->customer->isLogged()) {
            abc_redirect($this->html->getSecureURL('account/account'));
        }

        $this->document->setTitle($this->language->get('heading_title'));

        //validate token
        $rtoken = (string)$this->request->get['rtoken'];
        /**
         * @var AEncryption $enc
         */
        $enc = ABC::getObjectByAlias('AEncryption', [$this->config->get('encryption_key')]);
        list($customer_id, $code) = explode("::", $enc->decrypt($rtoken));
        $customer_details = Customer::getCustomer($customer_id);
        if (empty($customer_id)
            || empty($customer_details['data']['password_reset'])
            || $customer_details['data']['password_reset'] != $code
        ) {
            $this->error['message'] = $this->language->get('error_reset_token');
            return $this->password();
        }

        if ($this->request->is_POST() && $this->_validatePassword($customer_id, $this->request->post)) {

            if (!$this->csrftoken->isTokenValid()) {
                $this->error['warning'] = $this->language->get('error_unknown');
                return false;
            }

            $this->customer->editPassword($customer_details['loginname'], $this->request->post['password']);

            H::event(
                'storefront\sendPasswordResetNotifyEmail',
                [new ABaseEvent($customer_details)]
            );

            //update data and remove password_reset code
            unset($customer_details['data']['password_reset']);
            /**
             * @var Customer $customer
             */
            $customer = Customer::find($customer_id);
            $customer->update(['data' => $customer_details['data']]);

            $this->session->data['success'] = $this->language->get('text_success');
            abc_redirect($this->html->getSecureURL('account/login'));
        }

        $this->loadLanguage('account/password');

        $this->document->resetBreadcrumbs();

        $this->document->addBreadcrumb([
            'href'      => $this->html->getHomeURL(),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);

        $this->document->addBreadcrumb([
            'href'      => $this->html->getURL('account/account'),
            'text'      => $this->language->get('text_account'),
            'separator' => $this->language->get('text_separator'),
        ]);

        $this->document->addBreadcrumb([
            'href'      => $this->html->getURL('account/forgotten/password'),
            'text'      => $this->language->get('text_forgotten'),
            'separator' => $this->language->get('text_separator'),
        ]);

        $this->view->assign('error_warning', $this->error['warning']);
        $this->view->assign('error_password', $this->error['password']);
        $this->view->assign('error_confirm', $this->error['confirm']);

        $form = new AForm();
        $form->setForm(['form_name' => 'PasswordFrm']);
        $form_open = $form->getFieldHtml(
            [
                'type'   => 'form',
                'name'   => 'PasswordFrm',
                'action' => $this->html->getSecureURL('account/forgotten/reset', '&rtoken='.$rtoken),
                'csrf'   => true,
            ]
        );
        $this->view->assign('form_open', $form_open);

        $password = $form->getFieldHtml(
            [
                'type'     => 'password',
                'name'     => 'password',
                'value'    => '',
                'required' => true,
            ]);
        $confirm = $form->getFieldHtml(
            [
                'type'     => 'password',
                'name'     => 'password_confirmation',
                'value'    => '',
                'required' => true,
            ]);
        $submit = $form->getFieldHtml(
            [
                'type' => 'submit',
                'name' => $this->language->get('button_continue'),
                'icon' => 'fa fa-check',
            ]);

        $this->view->assign('password', $password);
        $this->view->assign('submit', $submit);
        $this->view->assign('confirm', $confirm);
        $this->view->assign('back', $this->html->getSecureURL('account/account'));

        $back = $this->html->buildElement(
            [
                'type'  => 'button',
                'name'  => 'back',
                'text'  => $this->language->get('button_back'),
                'icon'  => 'fa fa-arrow-left',
                'style' => 'button',
            ]);
        $this->view->assign('button_back', $back);

        $this->processTemplate('pages/account/password_reset.tpl');

        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function loginname()
    {

        $this->extensions->hk_InitData($this, __FUNCTION__);

        if ($this->customer->isLogged()) {
            abc_redirect($this->html->getSecureURL('account/account'));
        }

        $this->document->setTitle($this->language->get('heading_title_loginname'));

        if ($this->request->is_POST()) {
            $customer_details = $this->findCustomer('loginname', $this->request->post);
            if ($customer_details) {
                H::event(
                    'storefront\sendLoginNameEmail',
                    [new ABaseEvent($customer_details->toArray())]
                );

                $this->session->data['success'] = $this->language->get('text_success_loginname');
                abc_redirect($this->html->getSecureURL('account/login'));
            }
        }

        $this->document->resetBreadcrumbs();

        $this->document->addBreadcrumb([
            'href'      => $this->html->getHomeURL(),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);

        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('account/account'),
            'text'      => $this->language->get('text_account'),
            'separator' => $this->language->get('text_separator'),
        ]);

        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('account/forgotten/loginname'),
            'text'      => $this->language->get('text_forgotten_loginname'),
            'separator' => $this->language->get('text_separator'),
        ]);

        $this->view->assign('error', $this->error['message']);
        $this->view->assign('action', $this->html->getSecureURL('account/forgotten'));
        $this->view->assign('back', $this->html->getSecureURL('account/account'));

        $form = new AForm();
        $form->setForm(['form_name' => 'forgottenFrm']);
        $this->data['form']['form_open'] = $form->getFieldHtml(
            [
                'type'   => 'form',
                'name'   => 'forgottenFrm',
                'action' => $this->html->getSecureURL('account/forgotten/loginname'),
                'csrf'   => true,
            ]
        );

        $this->data['help_text'] = $this->language->get('text_lastname_email');
        $this->data['heading_title'] = $this->language->get('heading_title_loginname');

        $this->data['form']['fields']['lastname'] = $form->getFieldHtml([
            'type'  => 'input',
            'name'  => 'lastname',
            'value' => $this->request->post['lastname'],
        ]);
        $this->data['form']['fields']['email'] = $form->getFieldHtml([
            'type'  => 'input',
            'name'  => 'email',
            'value' => $this->request->post['email'],
        ]);

        $this->data['form']['continue'] = $form->getFieldHtml([
            'type' => 'submit',
            'name' => $this->language->get('button_continue'),
        ]);
        $this->data['form']['back'] = $form->getFieldHtml([
            'type'  => 'button',
            'name'  => 'back',
            'style' => 'button',
            'text'  => $this->language->get('button_back'),
        ]);
        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/account/forgotten.tpl');

        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

    }

    /**
     * @param string $mode -
     * @param $data
     *
     * @return bool|mixed
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws AException
     */
    protected function findCustomer($mode, $data)
    {
        $output = [];
        $email = $data['email'];
        $loginname = $data['loginname'];
        $lastname = $data['lastname'];
        //email is always required
        if (!isset($email) || empty($email)) {
            $this->error['message'] = $this->language->get('error_email');
            return false;
        }

        //locate customer based on login name
        if ($this->config->get('prevent_email_as_login') || $this->dcrypt->active) {
            if ($mode == 'password') {
                if (!empty($loginname)) {
                    $output = Customer::search([
                        'filter' =>
                            [
                                'search_operator' => 'equal',
                                'loginname'       => $loginname,
                                'email'           => $email,
                            ],
                    ]);
                } else {
                    $this->error['message'] = $this->language->get('error_loginname');
                    return false;
                }
            } else {
                if ($mode == 'loginname') {
                    if (!empty($lastname)) {
                        $output = Customer::search([
                            'filter' =>
                                [
                                    'search_operator' => 'equal',
                                    'lastname'        => $lastname,
                                    'email'           => $email,
                                ],
                        ]);
                    } else {
                        $this->error['message'] = $this->language->get('error_lastname');
                        return false;
                    }
                }
            }
        } else {
            //get customer by email
            $output = Customer::search([
                'filter' =>
                    [
                        'search_operator' => 'equal',
                        'email'           => $email,
                    ],
            ]);
        }

        if (!count($output)) {
            $this->error['message'] = $this->language->get('error_not_found');
            return false;
        } else {
            $output = $output->first();
            unset($output['total_num_rows']);
            return $output;
        }
    }

    private function _validatePassword($customer_id, $data)
    {
        $this->loadLanguage('account/password');

        $data['customer_id'] = $customer_id;

        $customer = new Customer();
        try {
            $customer->validate($data);
        } catch (ValidationException $e) {
            H::SimplifyValidationErrors($customer->errors()['validation'], $this->error);
        }

        if (!$this->error) {
            return true;
        } else {
            $this->error['warning'] = $this->language->get('gen_data_entry_error');
            return false;
        }
    }
}
