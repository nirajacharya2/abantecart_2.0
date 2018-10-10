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
namespace abc\controllers\admin;

use abc\core\engine\AController;
use abc\extensions\gdpr\models\admin\extension\ModelExtensionGdpr;

/**
 * Class ControllerResponsesExtensionDgpr
 *
 * @property ModelExtensionGdpr $model_extension_gdpr
 */
class ControllerResponsesExtensionGdpr extends AController
{

    public $data = [];
    public $error = [];

    public function erase()
    {

        $customer_id = (int)$this->request->get['customer_id'];
        if (!$customer_id) {
            abc_redirect($this->html->getSecureURL('sale/customer/update', '&customer_id='.$customer_id));
        }
        $this->loadModel('extension/gdpr');
        $this->model_extension_gdpr->erase($customer_id);
        abc_redirect($this->html->getSecureURL('sale/customer/update', '&customer_id='.$customer_id));
    }
}
