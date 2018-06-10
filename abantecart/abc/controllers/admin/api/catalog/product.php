<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2018 Belavier Commerce LLC
 *
 * This source file is subject to Open Software License (OSL 3.0)
 * License details is bundled with this package in the file LICENSE.txt.
 * It is also available at this URL:
 * <http://www.opensource.org/licenses/OSL-3.0>
 *
 * UPGRADE NOTE:
 * Do not edit or add to this file if you wish to upgrade AbanteCart to newer
 * versions in the future. If you wish to customize AbanteCart for your
 * needs please refer to http://www.abantecart.com for more information.
 */

namespace abc\controllers\admin;

use abc\core\engine\AControllerAPI;
use abc\models\base\Product;

if (!class_exists('abc\core\ABC') || !\abc\core\ABC::env('IS_ADMIN')) {
    header('Location: static_pages/?forbidden='.basename(__FILE__));
}

class ControllerApiCatalogProduct extends AControllerAPI
{
    const DEFAULT_STATUS = 1;

    /**
     * @return null
     */
    public function get()
    {
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $request = $this->rest->getRequestParams();
        if (!\H::has_value($request['product_id']) || !is_numeric($request['product_id'])) {
            $this->rest->setResponseData(array('Error' => 'Product ID is missing'));
            $this->rest->sendResponse(200);
            return null;
        }

        $procuct = Product::find($request['product_id']);
        if ($procuct === null) {
            $this->rest->setResponseData(array('Error' => "Product with ID {$request['product_id']} does not exist"));
            $this->rest->sendResponse(200);
            return null;
        }

        $data = $procuct->getAllData();

        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->rest->setResponseData($data);
        $this->rest->sendResponse(200);
    }

    public function post()
    {
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $request = $this->rest->getRequestParams();




        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->rest->setResponseData($customer_details);
        $this->rest->sendResponse(200);
    }

    private function createProduct() {

    }

    private function udpateProduct() {

    }

}