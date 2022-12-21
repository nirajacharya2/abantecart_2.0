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

namespace abc\controllers\storefront;

use abc\core\engine\AController;
use abc\core\engine\AForm;
use abc\core\lib\AMailIM;
use abc\models\customer\Customer;

class ControllerPagesAccountEdit extends AController
{
    public $error = [];

    public function __construct($registry, $instance_id, $controller, $parent_controller = '')
    {
        parent::__construct($registry, $instance_id, $controller, $parent_controller);
        $this->data['predefined_fields'] = [
            'loginname',
            'firstname',
            'lastname',
            'telephone',
            'email',
            'fax'
        ];
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function main()
    {
        /**
         * @var string $loginname
         * @var string $firstname
         * @var string $lastname
         * @var string $telephone
         * @var string $email
         * @var string $fax
         */
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->html->getSecureURL('account/edit');
            abc_redirect($this->html->getSecureURL('account/login'));
        }

        $this->document->setTitle($this->language->get('heading_title', 'account/edit'));

        $request_data = $this->request->post;
        if ($this->request->is_POST()) {
            $request_data['customer_id'] = $this->customer->getId();

            if ($this->csrftoken->isTokenValid()) {
                $this->error = $this->customer::validateRegistrationData($request_data);
                //if no update for loginname do not allow edit of username/loginname
                if ($this->customer->isLoginnameAsEmail()) {
                    //if allow login as email, need to set loginname = email in case email changed
                    if (!$this->config->get('prevent_email_as_login')) {
                        $request_data['loginname'] = $request_data['email'];
                    }
                }
            } else {
                $this->error['warning'] = $this->language->get('error_unknown');
            }

            if (!$this->error) {
                $this->customer->editCustomer($request_data);
                $this->session->data['success'] = $this->language->get('text_success');
                $this->extensions->hk_ProcessData($this);
                abc_redirect($this->html->getSecureURL('account/account'));
            }
        }

        //check if existing customer has loginname = email. Redirect if not allowed
        $reset_loginname = false;
        if ($this->config->get('prevent_email_as_login') && $this->customer->isLoginnameAsEmail()) {
            $this->error['warning'] = $this->language->get('loginname_update_required');
            $reset_loginname = true;
        }

        $this->document->resetBreadcrumbs();

        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getHomeURL(),
                'text'      => $this->language->get('text_home'),
                'separator' => false,
            ]
        );

        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSecureURL('account/account'),
                'text'      => $this->language->get('text_account'),
                'separator' => $this->language->get('text_separator'),
            ]
        );

        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSecureURL('account/edit'),
                'text'      => $this->language->get('text_edit'),
                'separator' => $this->language->get('text_separator'),
            ]
        );

        $this->data['error_warning'] = $this->error['warning'];
        $this->data['error_loginname'] = $this->error['loginname'];
        $this->data['error_firstname'] = $this->error['firstname'];
        $this->data['error_lastname'] = $this->error['lastname'];
        $this->data['error_email'] = $this->error['email'];
        $this->data['error_telephone'] = $this->error['telephone'];

        $customer_info = Customer::getCustomer($this->customer->getId());
        if ($customer_info) {
            $customer_info = $customer_info->toArray();
        }

        foreach ($this->data['predefined_fields'] as $field_name) {
            if (isset($request_data[$field_name])) {
                $$field_name = $request_data[$field_name];
            } elseif (isset($customer_info)) {
                $$field_name = $customer_info[$field_name];
            } else {
                $$field_name = '';
            }
        }

        $form = new AForm();
        $form->setForm(['form_name' => 'AccountFrm']);
        $this->data['form']['form_open'] = $form->getFieldHtml(
            [
                'type'   => 'form',
                'name'   => 'AccountFrm',
                'action' => $this->html->getSecureURL('account/edit'),
                'csrf'   => true,
            ]
        );

        $this->data['reset_loginname'] = $reset_loginname;

        if ($reset_loginname) {
            $this->data['form']['fields']['loginname'] = $form->getFieldHtml(
                [
                    'type'     => 'input',
                    'name'     => 'loginname',
                    'value'    => $loginname,
                    'style'    => 'highlight',
                    'required' => true,
                ]);
        } else {
            $this->data['form']['fields']['loginname'] = $loginname;
        }

        $this->data['form']['fields']['firstname'] = $form->getFieldHtml(
            [
                'type'     => 'input',
                'name'     => 'firstname',
                'value'    => $firstname,
                'required' => true,
            ]
        );
        $this->data['form']['fields']['lastname'] = $form->getFieldHtml(
            [
                'type'     => 'input',
                'name'     => 'lastname',
                'value'    => $lastname,
                'required' => true,
            ]
        );
        $this->data['form']['fields']['email'] = $form->getFieldHtml(
            [
                'type'     => 'input',
                'name'     => 'email',
                'value'    => $email,
                'required' => true,
            ]
        );
        $this->data['form']['fields']['telephone'] = $form->getFieldHtml(
            [
                'type'  => 'input',
                'name'  => 'telephone',
                'value' => $telephone,
            ]
        );
        $this->data['form']['fields']['fax'] = $form->getFieldHtml(
            [
                'type'     => 'input',
                'name'     => 'fax',
                'value'    => $fax,
                'required' => false,
            ]
        );

        //get only active IM drivers
        $im_drivers = $this->im->getIMDriverObjects();
        if ($im_drivers) {
            /** @var AMailIM $driver_obj */
            foreach ($im_drivers as $protocol => $driver_obj) {
                if (!is_object($driver_obj) || $protocol == 'email') {
                    continue;
                }

                if (isset($request_data[$protocol])) {
                    $value = $request_data[$protocol];
                } elseif (isset($customer_info)) {
                    $value = $customer_info[$protocol];
                }

                $fld = $driver_obj->getURIField($form, $value);
                $this->data['form']['fields'][$protocol] = $fld;
                $this->data['entry_' . $protocol] = $fld->label_text;
                $this->data['error_' . $protocol] = $this->error[$protocol];
            }
        }

        $this->data['form']['continue'] = $form->getFieldHtml(
            [
                'type' => 'submit',
                'icon' => 'fa fa-check',
                'name' => $this->language->get('button_continue'),
            ]
        );

        $this->data['form']['back'] = $form->getFieldHtml(
            [
                'type'  => 'button',
                'name'  => 'back',
                'style' => 'button',
                'icon'  => 'fa fa-arrow-left',
                'text'  => $this->language->get('button_back'),
            ]
        );

        $this->data['back'] = $this->html->getSecureURL('account/account');
        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/account/edit.tpl');

        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }
}