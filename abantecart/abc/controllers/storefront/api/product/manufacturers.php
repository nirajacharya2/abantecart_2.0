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

use abc\core\engine\AControllerAPI;

class ControllerApiProductManufacturers extends AControllerAPI
{
    /**
     * @OA\Get (
     *     path="/index.php/?rt=a/product/manufacturers",
     *     summary="Get manufacturers",
     *     description="Get list of manufacturers",
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
     *         description="Manufacturer data",
     *      @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="array",
     *                  @OA\Items(
     *                      ref="#/components/schemas/ManufacturerModel"
     *                   ),
     *                  @OA\Property(
     *                      description="Manufacturer list",
     *                  )
     *             )
     *         ),
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
        //TODO: Add support store_id and language_id
        //TODO: Remove old models usage.
        //TODO: Change Error response to standart

        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->loadModel('catalog/manufacturer');

        $data = $this->model_catalog_manufacturer->getManufacturers();

        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->rest->setResponseData($data);
        $this->rest->sendResponse(200);
    }

}

