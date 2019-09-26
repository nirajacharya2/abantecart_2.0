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
use abc\models\order\Order;
use H;


class ControllerApiOrderDetails extends AControllerAPI
{

    public function get()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('sale/order');

        $request = $this->rest->getRequestParams();

        if (!H::has_value($request['order_id'])) {
            $this->rest->setResponseData(['Error' => 'Order ID is missing']);
            $this->rest->sendResponse(200);
            return null;
        }

        $order_details = Order::getOrderArray($request['order_id'], 'any');
        if (!count($order_details)) {
            $this->rest->setResponseData(['Error' => 'Incorrect order ID or missing order data']);
            $this->rest->sendResponse(200);
            return null;
        }

        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->rest->setResponseData($order_details);
        $this->rest->sendResponse(200);
    }
}