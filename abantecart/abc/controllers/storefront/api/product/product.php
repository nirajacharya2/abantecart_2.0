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

use abc\core\ABC;
use abc\core\engine\AControllerAPI;
use abc\core\lib\APromotion;
use abc\core\engine\AResource;
use abc\models\catalog\Product;
use Illuminate\Support\Collection;

/**
 * Class ControllerApiProductProduct
 *
 * @package abc\controllers\storefront
 * @property \abc\models\storefront\ModelCatalogProduct $model_catalog_product
 * @property \abc\models\storefront\ModelCatalogReview  $model_catalog_review
 *
 */
class ControllerApiProductProduct extends AControllerAPI
{

    public function get()
    {
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $request = $this->rest->getRequestParams();

        $product_id = $request['product_id'];

        if (empty($product_id) || !is_numeric($product_id)) {
            $this->rest->setResponseData(['Error' => 'Missing or incorrect format product ID']);
            $this->rest->sendResponse(200);
            return null;
        }

        //Load all the data from the model
        /** @var Product|Collection $product */
        $product = Product::with('description', 'options.description')->with('tags')->find($product_id);

        if (!$product) {
            $this->rest->setResponseData( [ 'Error' => 'No product found' ] );
            $this->rest->sendResponse(200);
            return null;
        }
        //load resource library
        $resource = new AResource('image');
        $thumbnail = $resource->getMainThumb('products',
            $product_id,
            $this->config->get('config_image_thumb_width'),
            $this->config->get('config_image_thumb_height'));
        $product->thumbnail = $thumbnail['thumb_url'];

        $promotion = new APromotion();
        if ($this->config->get('config_customer_price') || $this->customer->isLogged()) {
            $product_price = $product->price;
            $discount = $promotion->getProductDiscount($product_id);
            if ($discount) {
                $product_price = $discount;
                $product->price = $this->currency->format(
                    $this->tax->calculate(
                        $discount,
                        $product->tax_class_id,
                        $this->config->get('config_tax')
                    )
                );
                $product->special = false;
            } else {
                $product->price = $this->currency->format(
                    $this->tax->calculate(
                        $product->price,
                        $product->tax_class_id,
                        $this->config->get('config_tax')
                    )
                );
                /**
                 * @var APromotion $promotion
                 */
                $promotion = ABC::getObjectByAlias('APromotion');
                $special = $promotion->getProductSpecial($product_id);

                if ($special) {
                    $product_price = $special;
                    $product->special = $this->currency->format(
                        $this->tax->calculate(
                            $special,
                            $product->tax_class_id,
                            $this->config->get('config_tax')
                        )
                    );
                } else {
                    $product->special = false;
                }
            }
            $product_discounts = $promotion->getProductDiscounts($product_id);
            $discounts = [];
            if ($product_discounts) {
                foreach ($product_discounts as $discount) {
                    $discounts[] = [
                        'quantity' => $discount['quantity'],
                        'price'    => $this->currency->format(
                            $this->tax->calculate(
                                $discount['price'],
                                $product->tax_class_id,
                                $this->config->get('config_tax')
                            )
                        ),
                    ];
                }
            }
            $product->discounts = $discounts;
            $product->product_price = $product_price;
        } else {
            //Do not Show price if setting and not logged in
            $product->product_price = '';
            $product->price = '';
        }

        if ($product->quantity <= 0) {
            $product->stock = $product->stock_status;
        } else {
            if ($this->config->get('config_stock_display')) {
                $product->stock = $product->quantity;
            } else {
                $product->stock = $this->language->get('text_instock');
            }
        }
        //hide quantity
        unset($product->quantity);

        if (!$product->minimum) {
            $product->minimum = 1;
        }

        $product->description = html_entity_decode(
            $product->description,
            ENT_QUOTES,
            ABC::env('APP_CHARSET')
        );




        $this->loadModel('catalog/review');
        if ($this->config->get('enable_reviews')) {
            $average = $this->model_catalog_review->getAverageRating($product_id);
            $product->text_stars = sprintf($this->language->get('text_stars'), $average);
            $product->stars = sprintf($this->language->get('text_stars'), $average);
            $product->average_rating = $average;
        }
        $product->update(
            [
              'count'=> $this->db->raw('count+1')
            ]
        );
//        //$this->model_catalog_product->updateViewed($product_id);
//
//        $tags = [];
//        $results = $this->model_catalog_product->getProductTags($product_id);
//        if ($results) {
//            foreach ($results as $result) {
//                if ($result['tag']) {
//                    $tags[] = ['tag' => $result['tag']];
//                }
//            }
//        }
//        $product_info['tags'] = $tags;

        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->rest->setResponseData($product );
        $this->rest->sendResponse(200);
    }

}