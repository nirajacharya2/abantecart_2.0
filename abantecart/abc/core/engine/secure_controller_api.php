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

namespace abc\core\engine;

class ASecureControllerAPI extends AControllerAPI
{
    public function main()
    {
        if ($this->config->get('config_maintenance')) {
            $this->rest->setResponseData([
                'error_code'=> 503,
                'error_text'=>'Maintenance mode'
            ]);
            $this->rest->sendResponse( 503 );
            return null;
        }

        if (!$this->isLoggedIn()) {
            $this->rest->setResponseData([
                'error_code'  => 401,
                'error_title' => 'Unauthorized',
                'error_text' => 'Not logged in or Login attempt failed!'
            ]);
            $this->rest->sendResponse(401);
            return null;
        }

        //call methods based on REST re	quest type
        switch ($this->rest->getRequestMethod()) {
            case 'get':
                return $this->get();
            case 'post':
                return $this->post();
            case 'put':
                return $this->put();
            case 'delete':
                return $this->delete();
            default:
                $this->rest->sendResponse(405);
                return null;
        }
    }

    private function isLoggedIn() {
        $headers = $this->request->getHeaders();
        $token = $this->rest->getRequestParam('token');
        if (!$headers || (!$headers['Authorization'] && !$token)) {
            return false;
        }

        $token = str_replace('Bearer ', '', $headers['Authorization'])
            //deprecated way. do not send token via post!
            ?: $token;
        return $token ? $this->customer->isLoggedWithToken($token) : false;
    }
}
