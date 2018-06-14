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
use abc\modules\events\ABaseEvent;
use abc\core\lib\AException;

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

        $getBy = null;
        if (isset($request['product_id']) && $request['product_id']) {
            $getBy = 'product_id';
        }
        if (isset($request['get_by']) && $request['get_by']) {
            $getBy = $request['get_by'];
        }

        if (!\H::has_value($getBy) || !isset($request[$getBy])) {
            $this->rest->setResponseData(array('Error' => $getBy.' is missing'));
            $this->rest->sendResponse(200);
            return null;
        }

        $product = Product::where([$getBy => $request[$getBy]]);
        if ($product === null) {
            $this->rest->setResponseData(
                array('Error' => "Product with ".$getBy." ".$request[$getBy]." does not exist")
            );
            $this->rest->sendResponse(200);
            return null;
        }

        $data = [];
        $item = $product->first();
        if($item) {
            $data = $item->getAllData();
        }
        if(!$data){
            $data = ['Error' => 'Requested Product Not Found'];
        }

        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->rest->setResponseData($data);
        $this->rest->sendResponse(200);
    }

    /**
     * @throws \Exception
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

        try {
            if ($updateBy) {
                $product = Product::where($updateBy, $request[$updateBy])->first();
                if ($product === null) {
                    $this->rest->setResponseData(
                        ['Error' => "Product with {$updateBy}: {$request[$updateBy]} does not exist"]
                    );
                    $this->rest->sendResponse(200);
                    return null;
                }
                //expand fillable columns for extensions
                if ($this->data['fillable']) {
                    $product->addFillable($this->data['fillable']);
                }
                $product = $this->updateProduct($product, $updateBy, $request[$updateBy], $request);
                \H::event(
                    'abc\controllers\admin\api\catalog\product@update',
                    new ABaseEvent($product->toArray(), ['products'])
                );
            } else {
                $product = $this->createProduct($request);
                \H::event(
                    'abc\controllers\admin\api\catalog\product@create',
                    new ABaseEvent($product->toArray(), ['products'])
                );
            }
        } catch (AException $e) {
            $this->rest->setResponseData(['Error' => $e->getMessage()]);
            $this->rest->sendResponse(200);
            return null;
        }

        if ($product === false) {
            $this->rest->setResponseData(['Error' => "Product was not created. Please fill required fields."]);
            $this->rest->sendResponse(200);
            return null;
        }
        if ($product->errors()) {
            $this->rest->setResponseData($product->errors());
            $this->rest->sendResponse(200);
            return null;
        }
        if (!$product_id = $product->getKey()) {
            $this->rest->setResponseData(['Error' => "Product was not created"]);
            $this->rest->sendResponse(200);
            return null;
        }

        $result = [
            'status'     => $updateBy ? 'updated' : 'created',
            'product_id' => $product_id,
        ];
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->rest->setResponseData($result);
        $this->rest->sendResponse(200);
    }

    /**
     * @param $data
     *
     * @return Product
     * @throws \Exception
     */
    private function createProduct($data)
    {
        if (!$data['descriptions'] || !current($data['descriptions'])['name']) {
            return false;
        }

        $expected_relations = ['descriptions', 'tags', 'categories', 'stores', 'options'];
        $rels = [];
        foreach ($expected_relations as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                $rels[$key] = $data[$key];
                unset($data[$key]);
            }
        }
        //create product
        $product = new Product();
        //expand fillable columns for extensions
        if ($this->data['fillable']) {
            $product->addFillable($this->data['fillable']);
        }

        $fillables = $product->getFillable();
        foreach ($fillables as $fillable) {
            $product->{$fillable} = $data[$fillable];
            $update_arr[$fillable] = $data[$fillable];
        }
        $this->log->write(var_export($update_arr, true));
        //TODO: NEED TO CHECK WHY WE CANNOT USE create STATIC METHOD HERE!!!!
        $product->save();
        //Product::create($data);

        if (!$product || !$product->getKey()) {
            $this->rest->setResponseData(['Error' => "Product cannot be created"]);
            $this->rest->sendResponse(200);
            return null;
        }
        //create defined relationships
        $product->updateRelationships($rels);

        $product->updateImages($data);

        return $product;
    }

    /**
     * @param Product $product
     * @param $data
     *
     * @return mixed
     */
    private function updateProduct($product, $updateBy, $value, $data)
    {

        $fillables = $product->getFillable();

        $update_arr = [];
        foreach ($fillables as $fillable) {
            $update_arr[$fillable] = $data[$fillable];
        }

        $expected_relations = ['descriptions', 'tags', 'categories', 'stores', 'options'];
        $rels = [];
        foreach ($expected_relations as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                $rels[$key] = $data[$key];
                unset($data[$key]);
            }
        }
        $this->log->write(var_export($update_arr, true));
        Product::where($updateBy, $value)->update($update_arr);

        $product->updateRelationships($rels);

        $product->updateImages($data);

        return $product;
    }
}