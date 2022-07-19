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
use stdClass;

class ControllerApiProductFilter extends AControllerAPI
{
    /**
     * @OA\Get (
     *     path="/index.php/?rt=a/product/filter",
     *     summary="Get products",
     *     description="You can get all products assigned to matching category ID, Manufacturer ID, keyword, etc. One of following search parameters is required category_id, manufacturer_id or keyword",
     *     tags={"Product"},
     *     security={{"apiKey":{}}},
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         description="Category Id",
     *         @OA\Schema(
     *              type="integer"
     *          ),
     *      ),
     *     @OA\Parameter(
     *         name="manufacturer_id",
     *         in="query",
     *         description="Manufacturer Id",
     *         @OA\Schema(
     *              type="integer"
     *          ),
     *      ),
     *      @OA\Parameter(
     *         name="keyword",
     *         in="query",
     *         spaceDelimited=true,
     *         description="Keyword text to be searched in product name, mode or SKU (space separated words to be searched)",
     *        @OA\Schema(
     *              type="string"
     *          ),
     *      ),
     *     @OA\Parameter(
     *         name="match",
     *         in="query",
     *         description="Identify type of match for keyword based search. Allowed values: any, all or exact any - result will contain any of the matched keywords separated by space all - all keywords must be present in the result exact - will do matching of whole keyword.",
     *         @OA\Schema(
     *              type="string",
     *              enum={ "all", "any" }
     *          ),
     *      ),
     *     @OA\Parameter(
     *         name="pfrom",
     *         in="query",
     *         description="Search for price range from pfrom to pto price (Only starting pfrom or finishing price range pto can be specified.)",
     *         @OA\Schema(
     *              type="double",
     *          ),
     *      ),
     *     @OA\Parameter(
     *         name="pto",
     *         in="query",
     *         description="Search for price range from pfrom to pto price (Only starting pfrom or finishing price range pto can be specified.)",
     *         @OA\Schema(
     *              type="double",
     *          ),
     *      ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Show specific page for the result",
     *         @OA\Schema(
     *              type="integer"
     *          ),
     *      ),
     *     @OA\Parameter(
     *         name="rows",
     *         in="query",
     *         description="Number or results in one page set",
     *         @OA\Schema(
     *              type="integer"
     *          ),
     *      ),
     *     @OA\Parameter(
     *         name="sidx",
     *         in="query",
     *         description="Data result to use for sorting (Sort Index). Sorting is possible by name, model, price and sort_order",
     *         @OA\Schema(
     *              type="string",
     *              enum={ "name", "model", "price", "sort_order" },
     *          ),
     *      ),
     *      @OA\Parameter(
     *         name="sord",
     *         in="query",
     *         description="Sorting order direction. Possible values DESC descending and ASC ascending. (Default ASC sorting order)",
     *         @OA\Schema(
     *              type="string",
     *              enum={ "ASC", "DESC" },
     *          ),
     *      ),
     *       @OA\Parameter(
     *         name="_search",
     *         in="query",
     *         description="Parameter to identify that advanced search is performed with JSON based filter string.",
     *         @OA\Schema(
     *              type="boolean",
     *              enum={ true, false },
     *          ),
     *      ),
     *      @OA\Parameter(
     *         name="filters",
     *         in="query",
     *         description="JSON based string with set of parameters to perform advanced search and filtering. This filter string is based on advanced jGgrid searching.",
     *         @OA\Schema(
     *              type="boolean",
     *              enum={ true, false },
     *          ),
     *     example="{'groupOp':'AND','rules':[{'field':'name','op':'cn','data':'ab'}]}"
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
        //TODO: Add support store_id and language_id, maybe currency
        //TODO: Remove old models usage.
        //TODO: Change Error response to standart

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->loadModel('catalog/product');
        $filter_params = array('category_id', 'manufacturer_id', 'keyword', 'match', 'pfrom', 'pto');
        $grid_filter_params = array('name', 'description', 'model', 'sku');
        $filter_data = array(
            'method'             => 'get',
            'filter_params'      => $filter_params,
            'grid_filter_params' => $grid_filter_params,
        );

        $filter = new AFilter($filter_data);
        $filters = $filter->getFilterData();
        $category_id = $filter->getFilterParam('category_id');
        $manufacturer_id = $filter->getFilterParam('manufacturer_id');
        $keyword = $filter->getFilterParam('keyword');

        if (!$category_id && !$manufacturer_id && !$keyword) {
            $this->rest->setResponseData(array('Error' => 'Missing one of required product filter parameters'));
            $this->rest->sendResponse(200);
            return null;
        }

        //get total
        $total = 0;
        if ($keyword) {
            $total = $this->model_catalog_product->getTotalProducts($filters);
        } elseif ($category_id) {
            $total = $this->model_catalog_product->getTotalProductsByCategoryId($category_id);
        } elseif ($manufacturer_id) {
            $total = $this->model_catalog_product->getTotalProductsByManufacturerId($manufacturer_id);
        }

        if ($total > 0) {
            $total_pages = ceil($total / $filter->getParam('rows'));
        } else {
            $total_pages = 0;
        }

        //Preserved jqGrid JSON interface
        $response = new stdClass();
        $response->page = $filter->getParam('page');
        $response->total = $total_pages;
        $response->records = $total;
        $response->limit = $filters['limit'];
        $response->sidx = $filters['sort'];
        $response->sord = $filters['order'];
        $response->params = $filters;

        $results = array();
        if ($keyword) {
            $results = $this->model_catalog_product->getProducts($filters);
        } elseif ($category_id) {
            $results = $this->model_catalog_product->getProductsByCategoryId($category_id,
                $filters['sort'],
                $filters['order'],
                $filters['start'],
                $filters['limit']);
        } elseif ($manufacturer_id) {
            $results = $this->model_catalog_product->getProductsByManufacturerId($manufacturer_id,
                $filters['sort'],
                $filters['order'],
                $filters['start'],
                $filters['limit']);
        }

        $i = 0;
        if ($results) {

            $product_ids = array();
            foreach ($results as $result) {
                $product_ids[] = (int)$result['product_id'];
            }
            $resource = new AResource('image');
            $thumbnails = $resource->getMainThumbList(
                'products',
                $product_ids,
                $this->config->get('config_image_thumb_width'),
                $this->config->get('config_image_thumb_height')
            );

            foreach ($results as $result) {
                $thumbnail = $thumbnails[$result['product_id']];

                $response->rows[$i] = [
                    'id'   => $result['product_id'],
                    'cell' => [
                        'thumb'         => $thumbnail['thumb_url'],
                        'name'          => $result['name'],
                        'description'   => $result['description'],
                        'model'         => $result['model'],
                        'price'         => $this->currency->convert(
                            $result['final_price'],
                            $this->config->get('config_currency'),
                            $this->currency->getCode()
                        ),
                        'currency_code' => $this->currency->getCode(),
                        'rating'        => $result['rating'],
                    ],
                ];
                $i++;
            }
        }

        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->rest->setResponseData($response);
        $this->rest->sendResponse(200);
    }
}
