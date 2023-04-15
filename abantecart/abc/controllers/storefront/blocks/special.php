<?php  
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2023 Belavier Commerce LLC

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
use abc\models\catalog\Product;
use abc\modules\traits\ProductListingTrait;

class ControllerBlocksSpecial extends AController
{
    use ProductListingTrait;

    public function main()
    {
        list($sort, $order) = explode("-", $this->config->get('config_product_default_sort_order'));
        $this->data['search_parameters'] = [
            'start'       => $this->request->get_or_post('start') ?: 0,
            'limit'       => $this->config->get('config_special_limit'),
            'language_id' => $this->language->getLanguageID(),
            'sort'        => $sort,
            'order'       => $order
        ];

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('blocks/special');
        $this->data['heading_title'] = $this->language->get('heading_title', 'blocks/special');

        $this->data['button_add_to_cart'] = $this->language->get('button_add_to_cart');

        $results = Product::getProductSpecials($this->data['search_parameters']);
        $this->processList($results);

        if ($this->config->get('config_customer_price')) {
            $this->data['display_price'] = TRUE;
        } elseif ($this->customer->isLogged()) {
            $this->data['display_price'] = TRUE;
        } else {
            $this->data['display_price'] = FALSE;
        }
        $this->data['review_status'] = $this->config->get('enable_reviews');
        // framed needs to show frames for generic block.
		//If tpl used by listing block framed was set by listing block settings
		$this->data['block_framed'] = true;
		$this->view->batchAssign($this->data);
		$this->processTemplate();

		//init controller data
		$this->extensions->hk_UpdateData($this,__FUNCTION__);
	}
}