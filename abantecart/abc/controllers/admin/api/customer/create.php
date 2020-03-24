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
use abc\models\customer\Customer;
use H;

/**
 * Class ControllerApiCustomerCreate
 *
 * @package abc\controllers\admin
 */
class ControllerApiCustomerCreate extends AControllerAPI
{
    const DEFAULT_GROUP = 1;
    const APPROVAL = 1;

    public function post()
    {
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadModel('sale/customer_group');

        $request = $this->rest->getRequestParams();

        if (!H::has_value($request['firstname'])) {
            $this->rest->setResponseData(['Error' => 'Customer first name is missing']);
            $this->rest->sendResponse(200);
            return;
        }

        if (!H::has_value($request['lastname'])) {
            $this->rest->setResponseData(['Error' => 'Customer last name is missing']);
            $this->rest->sendResponse(200);
            return;
        }

        if (!H::has_value($request['email'])) {
            $this->rest->setResponseData(['Error' => 'Customer email is missing']);
            $this->rest->sendResponse(200);
            return;
        }

        //check if customer exists.
        $result = Customer::getTotalCustomers(['filter' => ['email' => $request['email']]]);
        if ($result) {
            $this->rest->setResponseData(['Error' => "Customer with email {$request['email']} already exists."]);
            $this->rest->sendResponse(200);
            return;
        }
        //check if login is unique
        $request['loginname'] = $request['loginname'] ?? $request['email'];
        if (!Customer::isUniqueLoginname($request['loginname'])) {
            $this->rest->setResponseData(
                ['Error' => "Customer with loginname {$request['loginname']} already exists."]
            );
            $this->rest->sendResponse(200);
            return;
        }

        //create customer first
        $request['customer_group_id'] = isset($request['customer_group_id'])
            ? $request['customer_group_id']
            : self::DEFAULT_GROUP;

        $request['approved'] = isset($request['approved']) ? $request['approved'] : self::APPROVAL;

        //encrypt customer data
        if ($this->dcrypt->active) {
            $request = $this->dcrypt->encrypt_data($request, 'customers');

        }

        $customer = new Customer($request);
        $customer->save();

        //todo Need to add address creation

        $customer_details = $customer->toArray();
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->rest->setResponseData($customer_details);
        $this->rest->sendResponse(200);
    }
}