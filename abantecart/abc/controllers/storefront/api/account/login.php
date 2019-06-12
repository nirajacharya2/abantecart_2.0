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
use abc\models\customer\Address;

class ControllerApiAccountLogin extends AControllerAPI
{

    public function post()
    {
        //This is login attempt
        $request = $this->rest->getRequestParams();
        if (isset($request['token'])) {
            //this is the request to authorized
            if ($this->customer->isLoggedWithToken($request['token'])) {
                $this->rest->setResponseData(['status' => 1, 'request' => 'authorized']);
                $this->rest->sendResponse(200);
                return null;
            } else {
                $this->rest->setResponseData(['status' => 0, 'request' => 'unauthorized']);
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
                    $this->rest->setResponseData(['status' => 0, 'error' => 'Unable to get session ID.']);
                    $this->rest->sendResponse(501);
                    return null;
                }
                $this->session->data['token'] = session_id();
                $this->rest->setResponseData([
                    'status'  => 1,
                    'success' => 'Logged in',
                    'token'   => $this->session->data['token'],
                ]);
                $this->rest->sendResponse(200);
                return null;
            } else {
                $this->rest->setResponseData(['status' => 0, 'error' => 'Login attempt failed!']);
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

            $address = Address::where('customer_id', '=', $this->customer->getAddressId())
                              ->orderBy('default', 'desc')->first();
            $this->session->data['country_id'] = $address->country_id;
            $this->session->data['zone_id'] = $address->zone_id;
            return true;
        }
    }
}