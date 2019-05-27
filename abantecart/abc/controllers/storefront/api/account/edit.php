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

use abc\core\engine\AControllerAPI;
use abc\models\customer\Customer;

if (!class_exists('abc\core\ABC')) {
    header('Location: static_pages/?forbidden='.basename(__FILE__));
}

class ControllerApiAccountEdit extends AControllerAPI
{
    protected $v_error = [];
    public $data;

    public function post()
    {
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $request_data = $this->rest->getRequestParams();

        if (!$this->customer->isLoggedWithToken($request_data['token'])) {
            $this->rest->setResponseData(['error' => 'Not logged in or Login attempt failed!']);
            $this->rest->sendResponse(401);
            return null;
        }

        $this->loadLanguage('account/edit');
        $this->loadLanguage('account/success');
        $this->v_error = $this->customer::validateRegistrationData($request_data);
        if (!$this->v_error) {
            $request_data['newsletter'] = 1;
            $this->customer->model()->update($request_data);
            $this->data['status'] = 1;
            $this->data['text_message'] = $this->language->get('text_success');
        } else {
            $this->data['status'] = 0;
            $this->data['error_warning'] = $this->v_error['warning'];
            $this->data['error_firstname'] = $this->v_error['firstname'];
            $this->data['error_lastname'] = $this->v_error['lastname'];
            $this->data['error_email'] = $this->v_error['email'];
            $this->data['error_telephone'] = $this->v_error['telephone'];
            return $this->buildResponse();
        }

        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->rest->setResponseData($this->data);
        $this->rest->sendResponse(200);
    }

    public function get()
    {
        $request_data = $this->rest->getRequestParams();

        if (!$this->customer->isLoggedWithToken($request_data['token'])) {
            $this->rest->setResponseData(['error' => 'Not logged in or Login attempt failed!']);
            $this->rest->sendResponse(401);
            return null;
        }

        return $this->buildResponse();
    }

    private function buildResponse()
    {
        //Get all required data fields for registration.
        $this->loadLanguage('account/create');
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $request_data = $this->rest->getRequestParams();

        $customer_info = Customer::getCustomer($this->customer->getId());

        if (isset($request_data['firstname'])) {
            $firstname = $request_data['firstname'];
        } elseif (isset($customer_info)) {
            $firstname = $customer_info['firstname'];
        } else {
            $firstname = '';
        }

        if (isset($request_data['lastname'])) {
            $lastname = $request_data['lastname'];
        } elseif (isset($customer_info)) {
            $lastname = $customer_info['lastname'];
        } else {
            $lastname = '';
        }

        if (isset($request_data['email'])) {
            $email = $request_data['email'];
        } elseif (isset($customer_info)) {
            $email = $customer_info['email'];
        } else {
            $email = '';
        }

        if (isset($request_data['telephone'])) {
            $telephone = $request_data['telephone'];
        } elseif (isset($customer_info)) {
            $telephone = $customer_info['telephone'];
        } else {
            $telephone = '';
        }

        if (isset($request_data['fax'])) {
            $fax = $request_data['fax'];
        } elseif (isset($customer_info)) {
            $fax = $customer_info['fax'];
        } else {
            $fax = '';
        }

        if (isset($request_data['newsletter'])) {
            $newsletter = $request_data['newsletter'];
        } elseif (isset($customer_info)) {
            $newsletter = $customer_info['newsletter'];
        } else {
            $newsletter = '';
        }

        $this->data['fields']['firstname'] = [
            'type'     => 'input',
            'name'     => 'firstname',
            'value'    => $firstname,
            'required' => true,
            'error'    => $this->v_error['firstname'],
        ];
        $this->data['fields']['lastname'] = [
            'type'     => 'input',
            'name'     => 'lastname',
            'value'    => $lastname,
            'required' => true,
            'error'    => $this->v_error['lastname'],
        ];
        $this->data['fields']['email'] = [
            'type'     => 'input',
            'name'     => 'email',
            'value'    => $email,
            'required' => true,
            'error'    => $this->v_error['email'],
        ];
        $this->data['fields']['telephone'] = [
            'type'  => 'input',
            'name'  => 'telephone',
            'value' => $telephone,
            'error' => $this->v_error['telephone'],
        ];
        $this->data['fields']['fax'] = [
            'type'     => 'input',
            'name'     => 'fax',
            'value'    => $fax,
            'required' => false,
        ];

        $this->data['fields']['newsletter'] = [
            'type'     => 'selectbox',
            'name'     => 'newsletter',
            'value'    => $newsletter,
            'required' => false,
        ];

        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->rest->setResponseData($this->data);
        $this->rest->sendResponse(200);
    }
}