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

use abc\core\engine\ASecureControllerAPI;

class ControllerApiAccountLogout extends ASecureControllerAPI
{
    /**
     * @OA\POST(
     *     path="/index.php/?rt=a/account/logout",
     *     description="Logout from session",
     *     summary="Logout",
     *     tags={"Account"},
     *     security={{"tokenAuth":{}, "apiKey":{}}},
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
        $this->logout();
        $this->rest->setResponseData(['status' => 1, 'success' => 'Logged out',]);
        $this->rest->sendResponse(200);
    }

    /**
     * @OA\Get(
     *     path="/index.php/?rt=a/account/logout",
     *     description="Logout from session",
     *     summary="Logout",
     *     tags={"Account"},
     *     security={{"tokenAuth":{}, "apiKey":{}}},
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
        $this->logout();
        $this->rest->setResponseData(
            [
                'status'  => 1,
                'success' => 'Logged out'
            ]
        );

        $this->rest->sendResponse(200);
    }

    protected function logout()
    {

        $this->customer->logout();
        $this->cart->clear();

        unset(
            $this->session->data['shipping_address_id'],
            $this->session->data['shipping_method'],
            $this->session->data['shipping_methods'],
            $this->session->data['payment_address_id'],
            $this->session->data['payment_method'],
            $this->session->data['payment_methods'],
            $this->session->data['comment'],
            $this->session->data['order_id'],
            $this->session->data['coupon']
        );

        session_destroy();
    }
}
