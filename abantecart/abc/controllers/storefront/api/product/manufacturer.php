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
use abc\models\catalog\Manufacturer;
use abc\models\storefront\ModelCatalogManufacturer;

/**
 * Class ControllerApiProductManufacturer
 *
 * @package abc\controllers\storefront
 * @property ModelCatalogManufacturer $model_catalog_manufacturer
 */
class ControllerApiProductManufacturer extends AControllerAPI
{
    /**
     * @OA\Get (
     *     path="/index.php/?rt=a/product/manufacturer",
     *     summary="Get manufacturer",
     *     description="Get manufacturer details",
     *     tags={"Product"},
     *     security={{"apiKey":{}}},
     *     @OA\Parameter(
     *         name="manufacturer_id",
     *         in="query",
     *         required=true,
     *         description="Manufacturer unique Id",
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
     *         description="Manufacturer data",
     *         @OA\JsonContent(ref="#/components/schemas/ManufacturerModel"),
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
        $manufacturer_id = $this->request->get['manufacturer_id'];
        $this->loadModel('catalog/manufacturer');

        if (!$manufacturer_id) {
            $this->rest->setResponseData([
                'error_code' => 400,
                'error_text' => 'Bad request',
            ]);
            $this->rest->sendResponse(400);
            return null;
        }

        $data = (new Manufacturer())->getManufacturer($manufacturer_id);

        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->rest->setResponseData($data);
        $this->rest->sendResponse(200);
    }

}

