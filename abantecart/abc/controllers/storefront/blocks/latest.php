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

class ControllerBlocksLatest extends AController
{
    use ProductListingTrait;
    public function main()
    {
        $this->data['search_parameters'] = [
            'language_id' => $this->language->getLanguageID(),
            'store_id'    => (int)$this->config->get('config_store_id'),
            'with_all'    => true,
            'limit'       => $this->config->get('config_latest_limit'),
            'sort'        => 'date_added',
            'order'       => 'desc',
        ];
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('blocks/latest');
        $this->view->assign('heading_title', $this->language->get('heading_title', 'blocks/latest'));
        $this->view->assign('button_add_to_cart', $this->language->get('button_add_to_cart'));
        $this->data['products'] = [];

        $results = Product::search($this->data['search_parameters']);
        $this->processList($results);

        $this->view->batchAssign($this->data);

        if ($this->config->get('config_customer_price')) {
            $display_price = true;
        } elseif ($this->customer->isLogged()) {
            $display_price = true;
        } else {
            $display_price = false;
        }
        $this->view->assign('block_framed', true);
        $this->view->assign('display_price', $display_price);
        $this->view->assign('review_status', $this->config->get('enable_reviews'));
        $this->processTemplate();

        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }
}