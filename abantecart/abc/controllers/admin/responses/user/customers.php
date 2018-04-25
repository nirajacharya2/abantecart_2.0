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
use abc\core\helper\AHelperUtils;
use abc\core\lib\AJson;

if (!class_exists('abc\core\ABC') || !\abc\core\ABC::env('IS_ADMIN')) {
	header('Location: static_pages/?forbidden='.basename(__FILE__));
}

/**
 * Class ControllerResponsesUserCustomers
 */
class ControllerResponsesUserCustomers extends AController {

	public function main() {

        //init controller data
        $this->extensions->hk_InitData($this,__FUNCTION__);

        $this->loadModel('sale/customer');
		$results = $customer_data = array();
		
		if (AHelperUtils::has_value($this->request->get['keyword'])) {
			$results = $this->model_sale_customer->getCustomersByKeyword($this->request->get['keyword']);
		}elseif(AHelperUtils::has_value($this->request->get['email'])){
			$results = $this->model_sale_customer->getCustomersByEmails($this->request->get['email']);
		}

		if($results){
			foreach ($results as $result) {
				$customer_data[] = array(
					'customer_id' => $result['customer_id'],
					'name'        => $result['firstname'] . ' ' . $result['lastname'] . ' (' . $result['email'] . ')'
				);
			}
		}
		
		  //update controller data
        $this->extensions->hk_UpdateData($this,__FUNCTION__);

        $this->load->library('json');
		$this->response->addJSONHeader();
		$this->response->setOutput(AJson::encode($customer_data));
	}

}
