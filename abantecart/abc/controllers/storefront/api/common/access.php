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
namespace abc\controllers\storefront;
use abc\core\engine\AControllerAPI;

if (!class_exists('abc\ABC')) {
	header('Location: static_pages/?forbidden='.basename(__FILE__));
}
class ControllerApiCommonAccess extends AControllerAPI {
	public function main() {
		//validate if API enabled and KEY matches. 
		if ( $this->config->get('config_storefront_api_status')	) {
			if ($this->config->get('config_storefront_api_key') && 
					( $this->config->get('config_storefront_api_key') == $this->request->post['api_key'] ||
					  $this->config->get('config_storefront_api_key') == $this->request->get['api_key'] )
				) {
				return null;
			} else if ( !$this->config->get('config_storefront_api_key') ) {
				return null;
			}
		}
		return $this->dispatch('api/error/no_access');
	}
}