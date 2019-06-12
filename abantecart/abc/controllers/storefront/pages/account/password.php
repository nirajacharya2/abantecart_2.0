<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2019 Belavier Commerce LLC

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

use abc\core\engine\AController;
use abc\core\engine\AForm;
use abc\models\customer\Customer;
use Illuminate\Validation\ValidationException;

class ControllerPagesAccountPassword extends AController
{
    public $error = [];

    public function main()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->html->getSecureURL('account/password');
            abc_redirect($this->html->getSecureURL('account/login'));
        }

        $this->document->setTitle($this->language->get('heading_title'));

        if ($this->request->is_POST() && $this->_validate()) {
            $this->customer->editPassword($this->customer->getLoginName(), $this->request->post['password']);
            $this->session->data['success'] = $this->language->get('text_success');
            abc_redirect($this->html->getSecureURL('account/account'));
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
                'href'      => $this->html->getSecureURL('account/password'),
                'text'      => $this->language->get('heading_title'),
                'separator' => $this->language->get('text_separator'),
            ]);

        $this->data['error_warning'] = $this->error['warning'];
        $this->data['error_current_password'] = $this->error['current_password'];
        $this->data['error_password'] = $this->error['password'];
        $this->data['error_password_confirmation'] = $this->error['confirmed'];

        $form = new AForm();
        $form->setForm(['form_name' => 'PasswordFrm']);
        $this->data['form_open'] = $form->getFieldHtml(
            [
                'type'   => 'form',
                'name'   => 'PasswordFrm',
                'action' => $this->html->getSecureURL('account/password'),
                'csrf'   => true,
            ]
        );

        $this->data['form']['fields']['current_password'] = $form->getFieldHtml(
            [
                'type'     => 'password',
                'name'     => 'current_password',
                'value'    => '',
                'required' => true,
            ]);
        $this->data['form']['fields']['password'] = $form->getFieldHtml(
            [
                'type'     => 'password',
                'name'     => 'password',
                'value'    => '',
                'required' => true,
            ]);
        $this->data['entry_password_confirmation'] = $this->language->get('entry_confirm');
        $this->data['form']['fields']['password_confirmation'] = $form->getFieldHtml(
            [
                'type'     => 'password',
                'name'     => 'password_confirmation',
                'value'    => '',
                'required' => true,
            ]);
        $this->data['submit'] = $form->getFieldHtml(
            [
                'type' => 'submit',
                'name' => $this->language->get('button_continue'),
                'icon' => 'fa fa-check',
            ]);


        $this->data['back_url'] = $this->html->getSecureURL('account/account');

        $this->data['button_back'] = $this->html->buildElement(
            [
                'type'  => 'button',
                'name'  => 'back',
                'text'  => $this->language->get('button_back'),
                'icon'  => 'fa fa-arrow-left',
                'style' => 'button',
            ]);

        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/account/password.tpl');

        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    private function _validate()
    {
        $post = $this->request->post;
        if (!$this->csrftoken->isTokenValid()) {
            $this->error['warning'] = $this->language->get('error_unknown');
            return false;
        }

        if (empty($post['current_password'])
            || !$this->customer->login($this->customer->getLoginName(), $post['current_password'])
        ) {
            $this->error['current_password'] = $this->language->get('error_current_password');
        }

        $customer = $this->customer->model();

        try{
            $customer->validate( $post );
        }catch(ValidationException $e){
            \H::SimplifyValidationErrors($customer->errors()['validation'], $this->error);
        }

        $this->extensions->hk_ValidateData($this);

        if (!$this->error) {
            return true;
        } else {
            $this->error['warning'] = $this->language->get('gen_data_entry_error');
            return false;
        }
    }
}
