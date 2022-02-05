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

use abc\core\engine\AController;
use abc\core\lib\AError;
use abc\core\lib\AJson;
use abc\core\lib\LibException;

class ControllerResponsesCheckoutProcess extends AController
{
    public $data = [];

    public function confirm()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->data['output'] = '';
        try {
            $data = array_merge($this->request->get, $this->request->post);
            $data['order_id'] = $this->session->data['order_id'];
            $this->checkout->confirmOrder($data);
        }catch(LibException $e){
            $error = new AError($e->getMessages());
            $error->toLog()->toMessages('Checkout Process Error');
            return $error->toJSONResponse(
                AC_ERR_USER_ERROR,
                [
                    'error' => true,
                    'error_text' => 'System Error: '.$e->getMessages(),
                    'error_title' => 'System Error: '.$e->getMessages()
                ]
            );
        }

        $this->load->library('json');
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
        $this->response->setOutput($this->data['output'] ? AJson::encode($this->data['output']) : null);
    }

    public function callback()
    {

    }
}