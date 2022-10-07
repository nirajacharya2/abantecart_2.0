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

namespace abc\controllers\storefront;

use abc\core\engine\AController;
use abc\core\engine\AResource;
use abc\core\engine\HtmlElementFactory;
use abc\models\catalog\Product;
use H;
use Illuminate\Support\Collection;

class ControllerPagesAccountWishlist extends AController
{
    public function main()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->html->getSecureURL('account/wishlist');
            abc_redirect($this->html->getSecureURL('account/login'));
        }

        $this->document->setTitle($this->language->get('heading_title'));

        $this->getWishList();

        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
        unset($this->session->data['success']);
    }

    private function getWishList()
    {
        $cart_rt = 'checkout/cart';
        //is this an embed mode
        if ($this->config->get('embed_mode')) {
            $cart_rt = 'r/checkout/cart/embed';
        }

        $this->document->setTitle($this->language->get('heading_title'));

        $this->document->resetBreadcrumbs();

        $this->document->addBreadcrumb(
            [
                'href' => $this->html->getHomeURL(),
                'text' => $this->language->get('text_home'),
                'separator' => false,
            ]
        );

        $this->document->addBreadcrumb(
            [
                'href' => $this->html->getSecureURL('account/account'),
                'text' => $this->language->get('text_account'),
                'separator' => $this->language->get('text_separator'),
            ]
        );

        $this->document->addBreadcrumb(
            [
                'href' => $this->html->getSecureURL('account/wishlist'),
                'text' => $this->language->get('heading_title'),
                'separator' => $this->language->get('text_separator'),
            ]
        );

        $wishList = $this->customer->getWishList();

        if ($wishList && count($wishList) > 0) {
            $this->loadModel('tool/seo_url');
            $results = Product::search(
                [
                    'filter' => ['include' => array_keys($wishList)],
                    'with_final_price' => true,
                    'with_option_count' => true,
//                    'limit'             => $this->config->get('config_latest_limit'),
                    'sort' => 'date_added',
                    'order' => 'desc',
                ]
            );
            if ($results) {
                $product_ids = $results->pluck('product_id')->toArray();
                //get thumbnails by one pass
                $resource = new AResource('image');
                $thumbnails = $resource->getMainThumbList(
                    'products',
                    $product_ids,
                    $this->config->get('config_image_product_width'),
                    $this->config->get('config_image_product_height')
                );

                /** @var Collection|Product $result */
                foreach ($results as $i => $result) {
                    $this->data['products'][$i] = $result->toArray();
                    $this->data['products'][$i]['thumbnails'] = $thumbnails[$result->product_id];
                    $this->data['products'][$i]['added'] = H::dateInt2Display($wishList[$result->product_id]);
                    $this->data['products'][$i]['add'] = $this->html->getSEOURL(
                        $result->option_count ? 'product/product' : $cart_rt,
                        '&product_id=' . $result->product_id,
                        true
                    );
                    $this->data['products'][$i]['href'] = $this->html->getSEOURL(
                        'product/product',
                        '&product_id=' . $result->product_id,
                        true
                    );
                }
            }

            if (isset($this->session->data['redirect'])) {
                $this->data['continue'] = str_replace('&amp;', '&', $this->session->data['redirect']);
                unset($this->session->data['redirect']);
            } else {
                $this->data['continue'] = $this->html->getHomeURL();
            }

            $this->view->assign('error', '');
            if ($this->session->data['error']) {
                $this->view->assign('error', $this->session->data['error']);
                unset($this->session->data['error']);
            }

            if ($this->config->get('config_customer_price')) {
                $display_price = true;
            } elseif ($this->customer->isLogged()) {
                $display_price = true;
            } else {
                $display_price = false;
            }
            $this->data['display_price'] = $display_price;

            $this->view->setTemplate('pages/account/wishlist.tpl');
        } else {
            $this->data['heading_title'] = $this->language->get('heading_title');
            $this->data['text_error'] = $this->language->get('text_empty_wishlist');

            $this->data['button_continue'] = HtmlElementFactory::create(
                [
                    'name' => 'continue',
                    'type' => 'button',
                    'text' => $this->language->get('button_continue'),
                    'href' => $this->html->getHomeURL(),
                    'style' => 'button',
                ]
            );

            $this->view->setTemplate('pages/error/not_found.tpl');
        }

        $this->data['cart'] = $this->html->getSecureURL($cart_rt);

        $this->view->batchAssign($this->data);
        $this->processTemplate();
    }
}