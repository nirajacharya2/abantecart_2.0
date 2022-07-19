<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright 2011-2018 Belavier Commerce LLC

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

class ControllerApiProductQuantity extends AControllerAPI
{

    /**
     * @OA\Get (
     *     path="/index.php/?rt=a/product/quantity",
     *     summary="Get product quantity",
     *     description="Get quantity of products",
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
     *         description="Quantity data",
     *         @OA\JsonContent(ref="#/components/schemas/QuantityResponseModel"),
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
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $request = $this->rest->getRequestParams();
        $response_arr = array();

        $product_id = $request['product_id'];
        $opt_val_id = $request['option_value_id'];

        if (empty($product_id) || !is_numeric($product_id)) {
            $this->rest->setResponseData([
                'error_code' => 400,
                'error_text' => 'Bad request',
            ]);
            $this->rest->sendResponse(400);
            return null;
        }

        if (!$this->config->get('config_storefront_api_stock_check')) {
            $this->rest->setResponseData([
                'error_code' => 403,
                'error_text' => 'Restricted access to stock check',
            ]);
            $this->rest->sendResponse(403);
            return null;
        }

        //Load all the data from the model
        $this->loadModel('catalog/product');
        $product_info = $this->model_catalog_product->getProduct($product_id);
        if (count($product_info) <= 0) {
            $this->rest->setResponseData([
                'error_code' => 404,
                'error_text' => 'No product found',
            ]);
            $this->rest->sendResponse(404);
            return null;
        }
        //filter data and return only QTY for product and option values

        $response_arr['quantity'] = $product_info['quantity'];
        $response_arr['stock_status'] = $product_info['stock_status'];
        if ($product_info['quantity'] <= 0) {
            $response_arr['quantity'] = 0;
        }

        $product_info['options'] = $this->model_catalog_product->getProductOptions($product_id);
        foreach ($product_info['options'] as $option) {
            foreach ($option['option_value'] as $option_val) {
                $response_arr['option_value_quantities'][] = array(
                    'product_option_value_id' => $option_val['product_option_value_id'],
                    'quantity'                => $option_val['quantity'],
                );
            }
        }

        if (isset($opt_val_id)) {
            //replace and return only option value quantity
            foreach ($response_arr['option_value_quantities'] as $option_val) {
                if ($option_val['product_option_value_id'] == $opt_val_id) {
                    $response_arr = $option_val;
                    if ($response_arr['quantity'] <= 0) {
                        $response_arr['quantity'] = 0;
                    }
                    break;
                }
            }
        }

        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->rest->setResponseData($response_arr);
        $this->rest->sendResponse(200);
    }
}
