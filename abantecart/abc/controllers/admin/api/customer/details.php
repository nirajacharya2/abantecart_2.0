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
use abc\core\helper\AHelperUtils;
use abc\models\customer\Customer;
use abc\models\customer\CustomerGroup;
use H;

/**
 * Class ControllerApiCustomerDetails
 *
 * @package abc\controllers\admin
 */
class ControllerApiCustomerDetails extends AControllerAPI
{
    public function get()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $request = $this->rest->getRequestParams();

        if (!H::has_value($request['customer_id'])) {
            $this->rest->setResponseData(['Error' => 'Customer ID is missing']);
            $this->rest->sendResponse(200);
            return null;
        }

        $customer_details = Customer::with('addresses')
                                    ->where('customer_id', '=', $request['customer_id'])
                                    ->get()->toArray();


        if (!$customer_details) {
            $this->rest->setResponseData(['Error' => 'Incorrect Customer ID or missing customer data']);
            $this->rest->sendResponse(200);
            return null;
        }

        //clean up data before display
        unset($customer_details['password'],$customer_details['cart']);
        $customer_details['customer_group'] = CustomerGroup::find($customer_details['customer_group_id'])->name;

        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->rest->setResponseData($customer_details);
        $this->rest->sendResponse(200);
    }
}