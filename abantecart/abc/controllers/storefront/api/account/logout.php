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

use abc\core\engine\AControllerAPI;

class ControllerApiAccountLogout extends AControllerAPI
{
    /**
     * @OA\POST(
     *     path="/index.php/?rt=a/account/logout",
     *     description="Logout from session",
     *     summary="Logout",
     *     tags={"Account"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/SecureRequestModel"),
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success response",
     *         @OA\JsonContent(ref="#/components/schemas/ApiSuccessResponse"),
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"),
     *     )
     * )
     */
    public function post()
    {
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $request_data = $this->rest->getRequestParams();

        if (!$this->customer->isLoggedWithToken($request_data['token'])) {
            $this->rest->setResponseData([
                'error_code' => 0,
                'error_title' => 'Unauthorized',
                'error_text' => 'Not logged in logout attempt failed!'
            ]);
            $this->rest->sendResponse(401);
            return null;
        } else {
            $this->logout();
            $this->rest->setResponseData(array('status' => 1, 'success' => 'Logged out',));
            $this->rest->sendResponse(200);
            return null;
        }
    }

    /**
     * @OA\Get(
     *     path="/index.php/?rt=a/account/logout",
     *     description="Logout from session",
     *     summary="Logout",
     *     tags={"Account"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/SecureRequestModel"),
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success response",
     *         @OA\JsonContent(ref="#/components/schemas/ApiSuccessResponse"),
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"),
     *     )
     * )
     */
    public function get()
    {
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $request_data = $this->rest->getRequestParams();

        if (!$this->customer->isLoggedWithToken($request_data['token'])) {
            $this->rest->setResponseData([
                'error_code' => 0,
                'error_title' => 'Unauthorized',
                'error_text' => 'Not logged in logout attempt failed!'
            ]);
            $this->rest->sendResponse(401);
            return null;
        } else {
            $this->logout();
            $this->rest->setResponseData(array('status' => 1, 'success' => 'Logged out',));
            $this->rest->sendResponse(200);
            return null;
        }
    }

    protected function logout()
    {

        $this->customer->logout();
        $this->cart->clear();

        unset($this->session->data['shipping_address_id']);
        unset($this->session->data['shipping_method']);
        unset($this->session->data['shipping_methods']);
        unset($this->session->data['payment_address_id']);
        unset($this->session->data['payment_method']);
        unset($this->session->data['payment_methods']);
        unset($this->session->data['comment']);
        unset($this->session->data['order_id']);
        unset($this->session->data['coupon']);

        if ($this->config->get('config_tax_store')) {
            $country_id = $this->config->get('config_country_id');
            $zone_id = $this->config->get('config_zone_id');
        } else {
            $country_id = $zone_id = 0;
        }
        $this->tax->setZone($country_id, $zone_id);
    }
}
