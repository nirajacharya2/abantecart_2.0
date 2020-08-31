<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2017 Belavier Commerce LLC

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
use abc\core\lib\AJson;
use abc\models\customer\Customer;
use H;

/**
 * Class ControllerResponsesUserCustomers
 */
class ControllerResponsesUserCustomers extends AController
{

    public function main()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $results = $customer_data = [];
        $filter = [];
        if (H::has_value($this->session->data['current_store_id'])) {
            $filter['store_id'] = (int)$this->session->data['current_store_id'];
        }

        if (H::has_value($this->request->get['keyword'])) {
            $filter['name_email'] = $this->request->get['keyword'];
            $results = Customer::search(['filter' => $filter]);
        } elseif (H::has_value($this->request->get['email'])) {
            $filter['email'] = $this->request->get['email'];
            $results = Customer::search(['filter' => ['email' => $this->request->get['email']]]);
        }

        if ($results) {
            foreach ($results->toArray() as $result) {
                $customer_data[] = [
                    'customer_id' => $result['customer_id'],
                    'name'        => $result['firstname'].' '.$result['lastname'].' ('.$result['email'].')',
                ];
            }
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->load->library('json');
        $this->response->addJSONHeader();
        $this->response->setOutput(AJson::encode($customer_data));
    }

}
