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

class ControllerApiCommonPreflight extends AControllerAPI
{
    public function main()
    {
        // This might require future improvement.
        if ($_SERVER["REQUEST_METHOD"] == 'OPTIONS') {
            $response = $this->response;
            $response->addHeader("Access-Control-Allow-Origin: ".$_SERVER['HTTP_ORIGIN']);
            $response->addHeader("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
            $response->addHeader("Access-Control-Allow-Credentials: true");
            $response->addHeader("Access-Control-Max-Age: 60");
            $this->rest->sendResponse(200);
        }
    }
}



