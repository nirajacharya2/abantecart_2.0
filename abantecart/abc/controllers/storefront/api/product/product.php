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

    /**
     * @OA\Get (
     *     path="/index.php/?rt=a/product/product",
     *     summary="Get product",
     *     description="Get product details",
     *     tags={"Product"},
     *     security={{"apiKey":{}}},
     *     @OA\Parameter(
     *         name="product_id",
     *         in="query",
     *         required=true,
     *         description="Product unique Id",
     *        @OA\Schema(
     *              type="integer"
     *          ),
     *      ),
     *    @OA\Parameter(
     *         name="language_id",
     *         in="query",
     *         required=true,
     *         description="Language Id",
     *        @OA\Schema(
     *              type="integer"
     *          ),
     *      ),
     *      @OA\Parameter(
     *         name="store_id",
     *         in="query",
     *         required=true,
     *         description="Store Id",
     *     @OA\Schema(
     *              type="integer"
     *          ),
     *      ),
     *     @OA\Response(
     *         response="200",
     *         description="Product data",
     *         @OA\JsonContent(ref="#/components/schemas/ProductModel"),
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Bad Request",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"),
     *     ),
     *     @OA\Response(
     *         response="403",
     *         description="Access denight",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"),
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Not Found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"),
     *     ),
     *      @OA\Response(
     *         response="500",
     *         description="Server Error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"),
     *     )
     * )
     *
     */
    public function get()
    {
        //TODO: Add support store_id and language_id
        //TODO: Remove old models usage.
        //TODO: Change Error response to standart
        //TODO: How to get price for customer? Discounts?

        $this->extensions->hk_InitData($this, __FUNCTION__);
        $request = $this->rest->getRequestParams();

        $product_id = $request['product_id'];

        if (empty($product_id) || !is_numeric($product_id)) {
            $this->rest->setResponseData([
                'error_code' => 400,
                'error_text' => 'Bad request',
            ]);
            $this->rest->sendResponse(400);
            return null;
        }

        //Load all the data from the model
        $this->loadModel('catalog/product');
        $product_info = $this->model_catalog_product->getProduct($product_id);
        if (count($product_info) <= 0) {
            $this->rest->setResponseData([
                'error_code' => 404,
                'error_text' => 'Product not found',
            ]);
            $this->rest->sendResponse(404);
            return null;
        }
        //load resource library
        $resource = new AResource('image');
        $thumbnail = $resource->getMainThumb('products',
            $product_id,
            $this->config->get('config_image_thumb_width'),
            $this->config->get('config_image_thumb_height'));
        $product_info['thumbnail'] = $thumbnail['thumb_url'];

        $promotion = new APromotion();
        if ($this->config->get('config_customer_price') || $this->customer->isLogged()) {
            $product_price = $product_info['price'];
            $discount = $promotion->getProductDiscount($product_id);
            if ($discount) {
                $product_price = $discount;
                $product_info['price'] = $this->currency->format($this->tax->calculate($discount,
                    $product_info['tax_class_id'],
                    $this->config->get('config_tax')));
                $product_info['special'] = false;
            } else {
                $product_info['price'] = $this->currency->format($this->tax->calculate($product_info['price'],
                    $product_info['tax_class_id'],
                    $this->config->get('config_tax')));
                /**
                 * @var APromotion $promotion
                 */
                $promotion = ABC::getObjectByAlias('APromotion');
                $special = $promotion->getProductSpecial($product_id);

                if ($special) {
                    $product_price = $special;
                    $product_info['special'] = $this->currency->format($this->tax->calculate($special,
                        $product_info['tax_class_id'],
                        $this->config->get('config_tax')));
                } else {
                    $product_info['special'] = false;
                }
            }
            $product_discounts = $promotion->getProductDiscounts($product_id);
            $discounts = array();
            if ($product_discounts) {
                foreach ($product_discounts as $discount) {
                    $discounts[] = array(
                        'quantity' => $discount['quantity'],
                        'price'    => $this->currency->format(
                            $this->tax->calculate(
                                $discount['price'],
                                $product_info['tax_class_id'],
                                $this->config->get('config_tax')
                            )
                        ),
                    );
                }
            }
            $product_info['discounts'] = $discounts;
            $product_info['product_price'] = $product_price;
        } else {
            //Do not Show price if setting and not logged in
            $product_info['product_price'] = '';
            $product_info['price'] = '';
        }

        if ($product_info['quantity'] <= 0) {
            $product_info['stock'] = $product_info['stock_status'];
        } else {
            if ($this->config->get('config_stock_display')) {
                $product_info['stock'] = $product_info['quantity'];
            } else {
                $product_info['stock'] = $this->language->get('text_instock');
            }
        }
        //hide quantity
        unset($product_info['quantity']);

        if (!$product_info['minimum']) {
            $product_info['minimum'] = 1;
        }

        $product_info['description'] = html_entity_decode(
            $product_info['description'],
            ENT_QUOTES,
            ABC::env('APP_CHARSET')
        );

        $product_info['options'] = $this->model_catalog_product->getProductOptions($product_id);

        $this->loadModel('catalog/review');
        if ($this->config->get('enable_reviews')) {
            $average = $this->model_catalog_review->getAverageRating($product_id);
            $product_info['text_stars'] = sprintf($this->language->get('text_stars'), $average);

            $product_info['stars'] = sprintf($this->language->get('text_stars'), $average);
            $product_info['average'] = $average;
        }

        $this->model_catalog_product->updateViewed($product_id);

        $tags = array();
        $results = $this->model_catalog_product->getProductTags($product_id);
        if ($results) {
            foreach ($results as $result) {
                if ($result['tag']) {
                    $tags[] = array('tag' => $result['tag']);
                }
            }
        }
        $product_info['tags'] = $tags;

        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->rest->setResponseData($product_info);
        $this->rest->sendResponse(200);
    }

}
