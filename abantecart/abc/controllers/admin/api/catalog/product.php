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

/**
 * Class ControllerApiCatalogProduct
 *
 * @package abc\controllers\admin
 */
class ControllerApiCatalogProduct extends AControllerAPI
{
    /**
     *
     */
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

        $product = Product::find($request['product_id']);
        if ($product === null) {
            $this->rest->setResponseData(array('Error' => "Product with ID {$request['product_id']} does not exist"));
            $this->rest->sendResponse(200);
            return null;
        }

        $data = $product->getAllData();

        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->rest->setResponseData($data);
        $this->rest->sendResponse(200);
    }

    /**
     * @return null
     */
    public function post()
    {
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $request = $this->rest->getRequestParams();
        //are we updating or creating
        $updateBy = null;
        if (isset($request['product_id']) && $request['product_id']) {
            $updateBy = 'product_id';
        }
        if (isset($request['update_by']) && $request['update_by']) {
            $updateBy = $request['update_by'];
        }
        $product = null;
        if ($updateBy) {
            $product = Product::where([$updateBy => $request[$updateBy]])->first();
            if ($product === null) {
                $this->rest->setResponseData(array('Error' => "Product with {$updateBy}: {$request[$updateBy]} does not exist"));
                $this->rest->sendResponse(200);
                return null;
            }
            $product = $this->udpateProduct($product, $request);
        } else {
            $product = $this->createProduct($request);
        }

        if ($product->errors()) {
            $this->rest->setResponseData($product->errors());
            $this->rest->sendResponse(200);
        }
        if (!$product_id = $product->getKey()) {
            $this->rest->setResponseData("Product was not created");
            $this->rest->sendResponse(200);
            return null;
        }

        $result = [
            'status' => $updateBy ? 'updated' : 'created',
            'product_id'  => $product_id
        ];
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->rest->setResponseData($result);
        $this->rest->sendResponse(200);
    }

    /**
     * @param $data
     *
     * @return Product
     */
    private function createProduct($data) {
        $product = new Product();
        $product->fill($data)->save();
        //create defined relationships
        $expected_relations = ['descriptions', 'tags', 'options'];
        $rels = [];
        foreach ($expected_relations as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                $rels[$key] = $data[$key];
            }
        }
        $product->updateRelationships($rels);

        if (isset($data['categories'])) {
            $product->updateCategories($data['categories']);
        }

        if (isset($data['stores'])) {
            $product->updateStores($data['stores']);
        }

        return $product;
    }

    /**
     * @param $product
     * @param $data
     *
     * @return mixed
     */
    private function udpateProduct($product, $data) {
        $product->update($data);
        $expected_relations = ['descriptions', 'tags', 'options'];
        $rels = [];
        foreach ($expected_relations as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                $rels[$key] = $data[$key];
            }
        }
        $product->updateRelationships($rels);
        if (isset($data['categories'])) {
            $product->updateCategories($data['categories']);
        }
        if (isset($data['stores'])) {
            $product->updateStores($data['stores']);
        }
        return $product;
    }
}