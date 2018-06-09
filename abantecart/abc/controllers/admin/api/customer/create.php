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

use abc\core\engine\AControllerAPI;

if (!class_exists('abc\core\ABC') || !\abc\core\ABC::env('IS_ADMIN')) {
    header('Location: static_pages/?forbidden='.basename(__FILE__));
}

class ControllerApiCustomerCreate extends AControllerAPI
{
    const DEFAULT_GROUP = 1;
    const APROVAL = 1;

    public function post()
    {
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadModel('sale/customer');
        $this->loadModel('sale/customer_group');

        $request = $this->rest->getRequestParams();

        if (!\H::has_value($request['firstname'])) {
            $this->rest->setResponseData(array('Error' => 'Customer first name is missing'));
            $this->rest->sendResponse(200);
            return;
        }

        if (!\H::has_value($request['lastname'])) {
            $this->rest->setResponseData(array('Error' => 'Customer last name is missing'));
            $this->rest->sendResponse(200);
            return;
        }

        if (!\H::has_value($request['email'])) {
            $this->rest->setResponseData(array('Error' => 'Customer email is missing'));
            $this->rest->sendResponse(200);
            return;
        }

        //check if customer exists.
        $customer_id = '';
        $result = $this->model_sale_customer->getCustomersByEmails($request['email']);
        if (sizeof($result)) {
            $this->rest->setResponseData(array('Error' => "Customer with email {$request['email']} already exists." ));
            $this->rest->sendResponse(200);
            return;
        }
        //check if login is unique
        $request['loginname'] = isset($request['loginname']) ? $request['loginname'] : $request['email'];
        if (!$this->model_sale_customer->is_unique_loginname($request['loginname'])) {
            $this->rest->setResponseData(
                array('Error' => "Customer with loginname {$request['loginname']} already exists.")
            );
            $this->rest->sendResponse(200);
            return;
        }

        //create customer first
        $request['customer_group_id'] = isset($request['customer_group_id']) ? $request['customer_group_id'] : self::DEFAULT_GROUP;
        $request['approved'] = isset($request['approved']) ? $request['approved'] : self::APROVAL;

        $customer_id = $this->model_sale_customer->addCustomer($request);
        //todo Need to add address creation

        $customer_details = $this->model_sale_customer->getCustomer($customer_id);
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->rest->setResponseData($customer_details);
        $this->rest->sendResponse(200);
    }
}