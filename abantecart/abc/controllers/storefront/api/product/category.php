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

use abc\core\ABC;
use abc\core\engine\AControllerAPI;
use abc\core\engine\AResource;
use abc\models\catalog\Category;

/**
 * Class ControllerApiProductCategory
 *
 * @package abc\controllers\storefront
 */
class ControllerApiProductCategory extends AControllerAPI
{
    /**
     * @OA\Get (
     *     path="/index.php/?rt=a/product/category",
     *     summary="Get category",
     *     description="Get category with subcategories info",
     *     tags={"Product"},
     *     security={{"apiKey":{}}},
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         description="Category Id",
     *      ),
     *    @OA\Parameter(
     *         name="language_id",
     *         in="query",
     *         required=true,
     *         description="Language Id",
     *      ),
     *      @OA\Parameter(
     *         name="store_id",
     *         in="query",
     *         required=true,
     *         description="Store Id",
     *      ),
     *     @OA\Response(
     *         response="200",
     *         description="Category data",
     *         @OA\JsonContent(ref="#/components/schemas/GetCategoryModel"),
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
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $categoryId = 0;
        if (isset($this->request->get['category_id'])) {
            $categoryId = $this->request->get['category_id'];
        }

        if (!isset($this->request->get['store_id']) || !isset($this->request->get['language_id'])) {
            $this->rest->setResponseData([
                'error_code' => 400,
                'error_text' => 'Bad request',
            ]);
            $this->rest->sendResponse(400);
            return;
        }
        $storeId = $this->request->get['store_id'];
        $languageId = $this->request->get['language_id'];

        if ($categoryId !== 0) {
            $category_info = $this->getCategoryDetails($categoryId, $languageId, $storeId);
        } else {
            $category_info['category_id'] = $categoryId;
            $category_info['subcategories'] = $this->getCategories($categoryId, $languageId, $storeId);
        }
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        if (!$category_info) {
            $this->rest->setResponseData([
                'error_code' => 404,
                'error_text' => 'Category not found',
            ]);
            $this->rest->sendResponse(404);
            return;
        }

        $this->rest->setResponseData($category_info);
        $this->rest->sendResponse(200);
    }

    public function getCategoryDetails($category_id, $languageId, $storeId)
    {
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->loadModel('tool/image');

        $category_info = Category::getCategory($category_id, $storeId, 0, $languageId);
        if (!$category_info) {
            return [];
        }
        $resource = new AResource('image');
        $thumbnail = $resource->getMainThumb('categories',
            $category_id,
            $this->config->get('config_image_category_width'),
            $this->config->get('config_image_category_height'));
        $category_info['thumbnail'] = $thumbnail['thumb_url'];

        //Process data for category
        $category_info['description'] = html_entity_decode(
            $category_info['description'],
            ENT_QUOTES,
            ABC::env('APP_CHARSET')
        );
        $category_info['total_products'] = Category::getTotalActiveProductsByCategoryId($category_id, $storeId);
        $category_info['total_subcategories'] = Category::getTotalCategoriesByCategoryId($category_id);
        if ($category_info['total_products']) {
            $category_info['subcategories'] = $this->getCategories($category_id, $languageId, $storeId);
        }
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
        return $category_info;
    }

    public function getCategories($parent_category_id = 0, $languageId = 1 , $storeId = 0)
    {
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $results = Category::getCategories($parent_category_id, $storeId, 0, $languageId);

        $categories = [];
        $category_ids = array_map('intval', array_column($results, 'category_id'));

        //get thumbnails by one pass
        $resource = new AResource('image');
        $thumbnails = $resource->getMainThumbList(
            'categories',
            $category_ids,
            $this->config->get('config_image_category_width'),
            $this->config->get('config_image_category_height')
        );

        foreach ($results as $k => $result) {
            $thumbnail = $thumbnails[$result['category_id']];
            $categories[$k] = $result;
            $categories[$k]['thumb'] =  $thumbnail['thumb_url'];
            $categories[$k]['total_subcategories'] = Category::getTotalCategoriesByCategoryId($result['category_id']);
        }

        $this->extensions->hk_UpdateData($this, __FUNCTION__);
        return $categories;
    }
}
