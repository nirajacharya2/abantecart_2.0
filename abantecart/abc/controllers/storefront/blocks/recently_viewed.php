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

/**
 * Class ControllerBlocksRecentlyViewed
 *
 */
class ControllerBlocksRecentlyViewed extends AController
{
    use ProductListingTrait;

    public function main()
    {
        if (!(array)$this->session->data['recently_viewed']) {
            return;
        }
        $this->data['search_parameters'] = [
            'initiator'    => __METHOD__,
            'filter'       => [
                'include'     => array_map('intval', (array)$this->session->data['recently_viewed']),
                'language_id' => $this->language->getContentLanguageID(),
                'store_id'    => $this->config->get('config_store_id')
            ],
            'limit'        => 20,
            //NOTE: sorting by giver product_ids sequence (see $bestSellerIds var)
            'sort'         => 'include',
            'only_enabled' => true,
            'with_all'     => true
        ];
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->data['heading_title'] = $this->language->get('heading_title', 'blocks/recently_viewed');
        $this->data['button_add_to_cart'] = $this->language->get('button_add_to_cart');
        $results = Product::getProducts($this->data['search_parameters']);
        $this->processList($results);

        if ($this->config->get('config_customer_price')) {
            $this->data['display_price'] = true;
        } elseif ($this->customer->isLogged()) {
            $this->data['display_price'] = true;
        } else {
            $this->data['display_price'] = false;
        }
        $this->data['review_status'] = $this->config->get('enable_reviews');
        // framed needs to show frames for generic block.
        //If tpl used by listing block framed was set by listing block settings
        $this->data['block_framed'] = true;
        $this->view->batchAssign($this->data);
        $this->processTemplate();

        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }
}