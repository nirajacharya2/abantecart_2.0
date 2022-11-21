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

use abc\core\engine\AControllerAPI;
use abc\core\engine\AResource;
use abc\core\lib\AFilter;
use abc\models\catalog\Product;
use Illuminate\Support\Collection;
use stdClass;

class ControllerApiProductLatest extends AControllerAPI
{

    /**
     * @OA\Get (
     *     path="/index.php/?rt=a/product/latest",
     *     summary="Get latest products",
     *     description="Get kist of latest products",
     *     tags={"Product"},
     *     security={{"apiKey":{}}},
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
     *         description="Products",
     *         @OA\JsonContent(ref="#/components/schemas/GetProductsModel"),
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Bad Request",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"),
     *     ),
     *     @OA\Response(
     *         response="403",
     *         description="Access denied",
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

        //TODO: Add support store_id and language_id, maybe currency
        //TODO: Remove old models usage.
        //TODO: Change Error response to standart

        $this->extensions->hk_InitData($this, __FUNCTION__);

        $filter_data = [
            'method' => 'get',
        ];

        $filter = new AFilter($filter_data);
        $filters = $filter->getFilterData();

        $results = Product::search(
            [
                'with_final_price' => true,
                'with_rating'      => true,
                'limit' => $filters['limit'],
                'sort'  => 'date_added',
                'order' => 'desc',
            ]
        );

        $response = new stdClass();
        $response->page = $filter->getParam('page');
        $response->total = $results->count();
        $response->records = $filters['limit'];
        $response->limit = $filters['limit'];
        $response->sidx = $filters['sort'];
        $response->sord = $filters['order'];
        $response->params = $filters;


        if ($results) {
            $product_ids = $results->pluck('product_id')->toArray();
            $resource = new AResource('image');
            $thumbnails = $resource->getMainThumbList(
                'products',
                $product_ids,
                $this->config->get('config_image_thumb_width'),
                $this->config->get('config_image_thumb_height')
            );
            $i = 0;
            /** @var Collection $result */
            foreach ($results as $result) {
                $thumbnail = $thumbnails[$result['product_id']];
                $cell = $result->toArray();
                $cell['thumb'] = $thumbnail['thumb_url'];
                $cell['price'] = $this->currency->convert(
                    $result['final_price'],
                    $this->config->get('config_currency'),
                    $this->currency->getCode()
                );
                $cell['currency_code'] = $this->currency->getCode();

                $response->rows[$i] = [
                    'id'   => $result['product_id'],
                    'cell' => $cell,
                ];
                $i++;
            }
        }

        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->rest->setResponseData($response);
        $this->rest->sendResponse(200);
    }

}