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
use abc\core\engine\AResource;

class ControllerApiProductResources extends AControllerAPI
{

    /**
     * @OA\Get (
     *     path="/index.php/?rt=a/product/resources",
     *     summary="Get product resources",
     *     description="Get product resources",
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
     *     @OA\Parameter(
     *         name="resource_type",
     *         in="query",
     *         required=true,
     *         description="Product resource type",
     *        @OA\Schema(
     *              type="string",
     *              enum={"image", "pdf"}
     *          ),
     *      ),
     *     @OA\Response(
     *         response="200",
     *         description="Resources data",
     *         @OA\JsonContent(ref="#/components/schemas/ResourcesResponseModel"),
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
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $product_id = $this->request->get['product_id'];
        $resource_type = $this->request->get['resource_type'];

        if (!$product_id) {
            $this->rest->setResponseData([
                'error_code' => 400,
                'error_text' => 'Bad request',
            ]);
            $this->rest->sendResponse(400);
            return null;
        }

        $resources = array();
        $resource = new AResource('image');
        if ($resource_type == 'image') {
            $images = array();
            $results = $resource->getResources('products', $product_id, $this->config->get('storefront_language_id'));

            foreach ($results as $result) {
                $thumbnail = $resource->getResourceThumb(
                    $result['resource_id'],
                    $this->config->get('config_image_additional_width'),
                    $this->config->get('config_image_additional_height'));
                if ($thumbnail) {
                    $images[] = array(
                        'original' => ABC::env('HTTPS_DIR_RESOURCES').'image/'.$result['resource_path'],
                        'thumb'    => $thumbnail,
                    );
                }
            }
            $resources = $images;
        } else {
            //TODO: Add another resources type with response like in Images, add http/https to url's
//            if ($resource_type == 'pdf') {
                // TODO Add support to other types to return files or codes
//            } else {
//                $resource = new AResource('image');
//                //Getting all available types. NOTE there is no easy way, yet,
//                // to tell what resources are available in what type for given product.
//                //This is possible only in admin for now
//                $resources = $resource->getAllResourceTypes();
//            }
        }

        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->rest->setResponseData(array('total' => count($resources), 'resources' => $resources));
        $this->rest->sendResponse(200);
    }
}
