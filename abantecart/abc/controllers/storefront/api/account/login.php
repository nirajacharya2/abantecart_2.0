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

use abc\core\engine\AControllerAPI;
use abc\models\customer\Address;


class ControllerApiAccountLogin extends AControllerAPI
{

    /**
     * @OA\POST(
     *     path="/index.php/?rt=a/account/login",
     *     description="This API request needs to be done every time customer request to login to get access to customer account or just to confirm that current authentication is still valid and not expired.",
     *     summary="Login",
     *     tags={"Account"},
     *     security={{"apiKey":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/loginRequestModel"),
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success response",
     *         @OA\JsonContent(ref="#/components/schemas/loginSuccessModel"),
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/loginErrorModel"),
     *     ),
     *      @OA\Response(
     *         response="500",
     *         description="Server Error",
     *         @OA\JsonContent(ref="#/components/schemas/loginErrorModel"),
     *     )
     * )
     */
    public function post()
    {
        $this->extensions->hk_InitData($this, __FUNCTION__);
        //This is login attempt
        $request = $this->rest->getRequestParams();
        if (trim($request['token'])) {
            //this is the request to authorized
            if ($this->customer->isLoggedWithToken($request['token'])) {
                #update last_login date
                $this->customer->setLastLogin($this->customer->getId());
                $this->rest->setResponseData([
                    'status'  => 1,
                    'success' => 'authorized',
                    'token'   => $request['token']
                ]);
                $this->rest->sendResponse(200);
                return null;
            } else {
                $this->rest->setResponseData([
                        'error_code' => 0,
                        'error_title' => 'Unauthorized',
                        'error_text' => 'Unauthorized'
                    ]);
                $this->rest->sendResponse(401);
                return null;
            }

        } else {
            //support old email based login
            $loginname = (isset($request['loginname'])) ? $request['loginname'] : $request['email'];
            if (isset($loginname)
                && isset($request['password'])
                && $this->validate($loginname, $request['password'])
            ) {
                if (!session_id()) {
                    $this->rest->setResponseData([
                            'error_code' => 0,
                            'error_title' => 'Unauthorized',
                            'error_text' => 'Unable to get session ID.'
                        ]);
                    $this->rest->sendResponse(401);
                    return null;
                }
                $this->session->data['token'] = session_id();
                $this->data['response'] = [
                    'status'  => 1,
                    'success' => 'Logged in',
                    'token'   => $this->session->data['token'],
                ];

                $this->extensions->hk_UpdateData($this, __FUNCTION__);

                $this->rest->setResponseData($this->data['response']);
                $this->rest->sendResponse(200);
                return null;
            } else {
                $this->data['response'] = [
                    'error_code' => 0,
                    'error_title' => 'Unauthorized',
                    'error_text' => 'Login attempt failed!'
                ];
                $this->extensions->hk_UpdateData($this, __FUNCTION__);
                $this->rest->setResponseData($this->data['response']);
                $this->rest->sendResponse(401);
                return null;
            }
        }
    }

    protected function validate($loginname, $password)
    {
        if (!$this->customer->login($loginname, $password)) {
            return false;
        } else {
            unset($this->session->data['guest']);
            /** @var Address $address */
            $address = Address::where('customer_id', '=', $this->customer->getAddressId())
                              ->orderBy('address_id', 'desc')->first();
            $this->session->data['country_id'] = $address->country_id;
            $this->session->data['zone_id'] = $address->zone_id;
            return true;
        }
    }
}