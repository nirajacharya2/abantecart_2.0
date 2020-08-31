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

use abc\core\engine\AController;
use abc\core\engine\AResource;
use abc\core\helper\AHelperUtils;
use abc\models\catalog\Product;
use Illuminate\Support\Collection;

class ControllerBlocksLatest extends AController
{
    public $data;

    public function main()
    {

        //disable cache when login display price setting is off or enabled showing of prices with taxes
        if (($this->config->get('config_customer_price') && !$this->config->get('config_tax'))
            && $this->html_cache()) {
            return null;
        }

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('blocks/latest');
        $this->view->assign('heading_title', $this->language->get('heading_title', 'blocks/latest'));

        $this->loadModel('catalog/product');
        $this->loadModel('catalog/review');
        $this->loadModel('tool/image');

        $this->view->assign('button_add_to_cart', $this->language->get('button_add_to_cart'));
        $this->data['products'] = [];

        $results = Product::search(
            [
                'with_final_price'    => true,
                'with_discount_price' => true,
                'with_special_price'  => true,
                'with_rating'         => true,
                'with_stock_info'     => true,
                'with_option_count'   => true,
                'limit'               => $this->config->get('config_latest_limit'),
                'sort'                => 'date_added',
                'order'               => 'desc',
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

            $this->data['is_customer'] = true;
            if ($this->customer->isLogged() || $this->customer->isUnauthCustomer()) {
                $this->data['is_customer'] = true;
                $whishlist = $this->customer->getWishList();
            } else {
                $whishlist = [];
            }
            /** @var Collection $result */
            foreach ($results as $i => $result) {
                $thumbnail = $thumbnails[$result['product_id']];
                $rating = $result['rating'];
                $special = false;
                $discount = $result['discount_price'];

                if ($discount) {
                    $price = $this->currency->format(
                        $this->tax->calculate(
                            $discount,
                            $result['tax_class_id'],
                            $this->config->get('config_tax')
                        )
                    );
                } else {
                    $price = $this->currency->format(
                        $this->tax->calculate(
                            $result['price'],
                            $result['tax_class_id'],
                            $this->config->get('config_tax')
                        )
                    );
                    $special = $result['special_price'];
                    if ($special) {
                        $special = $this->currency->format(
                            $this->tax->calculate(
                                $special,
                                $result['tax_class_id'],
                                $this->config->get('config_tax')
                            )
                        );
                    }
                }

                $hasOptions = $result['option_count'];

                if ($hasOptions) {
                    $add = $this->html->getSEOURL(
                        'product/product',
                        '&product_id='.$result['product_id'],
                        '&encode'
                    );
                } else {
                    if ($this->config->get('config_cart_ajax')) {
                        $add = '#';
                    } else {
                        $add = $this->html->getSecureURL(
                            'checkout/cart',
                            '&product_id='.$result['product_id'],
                            '&encode'
                        );
                    }
                }

                //check for stock status, availability and config
                $track_stock = false;
                $in_stock = false;
                $no_stock_text = $this->language->get('text_out_of_stock');
                $total_quantity = 0;
                $stock_checkout = $result['stock_checkout'] === ''
                    ? $this->config->get('config_stock_checkout')
                    : $result['stock_checkout'];
                if ($result['subtract']) {
                    $track_stock = true;
                    $total_quantity = $result['quantity'];
                    //we have stock or out of stock checkout is allowed
                    if ($total_quantity > 0 || $stock_checkout) {
                        $in_stock = true;
                    }
                }

                $in_wishlist = false;
                if ($whishlist && $whishlist[$result['product_id']]) {
                    $in_wishlist = true;
                }

                $catalog_mode = false;
                if ($result['product_type_id']) {
                    $prodTypeSettings = Product::getProductTypeSettings((int)$result['product_id']);

                    if ($prodTypeSettings
                        && is_array($prodTypeSettings)
                        && isset($prodTypeSettings['catalog_mode'])
                    ) {
                        $catalog_mode = (bool)$prodTypeSettings['catalog_mode'];
                    }
                }

                $this->data['products'][$i] = $result->toArray();
                $this->data['products'][$i]['stars'] = sprintf($this->language->get('text_stars'), $rating);
                $this->data['products'][$i]['price'] = $price;
                $this->data['products'][$i]['options'] = $hasOptions;
                $this->data['products'][$i]['special'] = $special;
                $this->data['products'][$i]['thumb'] = $thumbnail;
                $this->data['products'][$i]['href'] = $this->html->getSEOURL(
                    'product/product',
                    '&product_id='.$result['product_id'],
                    '&encode'
                );
                $this->data['products'][$i]['add'] = $add;
                $this->data['products'][$i]['track_stock'] = $track_stock;
                $this->data['products'][$i]['in_stock'] = $in_stock;
                $this->data['products'][$i]['no_stock_text'] = $no_stock_text;
                $this->data['products'][$i]['total_quantity'] = $total_quantity;
                $this->data['products'][$i]['in_wishlist'] = $in_wishlist;
                $this->data['products'][$i]['product_wishlist_add_url'] = $this->html->getURL(
                    'product/wishlist/add',
                    '&product_id='.$result['product_id']
                );
                $this->data['products'][$i]['product_wishlist_remove_url'] = $this->html->getURL(
                    'product/wishlist/remove',
                    '&product_id='.$result['product_id']
                );
                $this->data['products'][$i]['catalog_mode'] = $catalog_mode;
            }
        }

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
