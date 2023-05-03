<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2022 Belavier Commerce LLC
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
use abc\core\engine\Registry;
use abc\models\catalog\Category;
use abc\models\catalog\Manufacturer;
use abc\models\catalog\Product;
use abc\models\catalog\UrlAlias;
use abc\models\QueryBuilder;
use abc\modules\events\ABaseEvent;
use abc\core\lib\AException;
use Exception;
use H;
use PDOException;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;

/**
 * Class ControllerApiCatalogProduct
 *
 * @package abc\controllers\admin
 *
 */
class ControllerApiCatalogProduct extends AControllerAPI
{
    public function get()
    {
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $request = $this->rest->getRequestParams();

        $this->data['request'] = $request;

        $getBy = null;
        if (isset($request['product_id']) && $request['product_id']) {
            $getBy = 'product_id';
        }
        if (isset($request['get_by']) && $request['get_by']) {
            $getBy = $request['get_by'];
        }

        if (!H::has_value($getBy) || !isset($request[$getBy])) {
            $this->rest->setResponseData(['Error' => $getBy.' is missing']);
            $this->rest->sendResponse(200);
            return;
        }
        /**
         * @var QueryBuilder $query
         */
        $query = Product::where([$getBy => $request[$getBy]]);
        if ($query === null) {
            $this->rest->setResponseData(
                ['Error' => "Product with ".$getBy." ".$request[$getBy]." does not exist"]
            );
            $this->rest->sendResponse(200);
            return;
        }

        $this->data['result'] = [];
        /** @var Product $item */
        $item = $query->first();
        if ($item) {
            $this->data['result'] = $item->getAllData();
        }
        if (!$this->data['result']) {
            $this->data['result'] = ['Error' => 'Requested Product Not Found'];
        }

        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->rest->setResponseData($this->data['result']);
        $this->rest->sendResponse(200);
    }

    /**
     * @throws Exception|InvalidArgumentException
     */
    public function post()
    {
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $request = $this->rest->getRequestParams();
        if (!$request) {
            return;
        }

        if (!is_array($request)) {
            throw new Exception('Request parameters are not in array: ' . var_export($request, true));
        }

        try {
            $this->data['request'] = $request;
            $request = $this->prepareData($request);

            //are we updating or creating
            $updateBy = (isset($request['product_id']) && $request['product_id'])
                ? 'product_id'
                : null;

            $updateBy = (isset($request['update_by']) && $request['update_by'])
                ? $request['update_by']
                : $updateBy;

            if ($updateBy) {
                /** @var Product|null|false $product */
                $product = Product::where($updateBy, $request[$updateBy])->first();
                if ($product === null) {
                    $this->rest->setResponseData(
                        [
                            'Error' => "Product with " . $updateBy . ": " . $request[$updateBy] . " does not exist"
                        ]
                    );
                    $this->rest->sendResponse(406);
                    return;
                }
                $product = $this->updateProduct($product, $request);
                if (is_object($product)) {
                    H::event(
                        'abc\controllers\admin\api\catalog\product@update',
                        new ABaseEvent($product->toArray(), ['products'])
                    );
                } else {
                    $product = false;
                }
            } else {
                $product = $this->createProduct($request);
                if (is_object($product)) {
                    H::event(
                        'abc\controllers\admin\api\catalog\product@create',
                        new ABaseEvent($product->toArray(), ['products'])
                    );
                } else {
                    $product = false;
                }
            }
        } catch (Exception|\Error $e) {
            $trace = $e->getTraceAsString();
            $this->log->error('Error: ' . $e->getMessage() . "\n" . $trace);
            $this->rest->setResponseData(['Error' => $e->getMessage()]);
            $this->rest->sendResponse(500);
            return;
        }
        if ($product === false) {
            $this->rest->setResponseData(['Error' => "Product was not " . ($updateBy ? 'updated' : 'created') . ". Incomplete Data."]);
            $this->rest->sendResponse(406);
            return;
        }
        if ($product->errors()) {
            $this->rest->setResponseData($product->errors());
            $this->rest->sendResponse(406);
            return;
        }
        if (!$product_id = $product->getKey()) {
            $this->rest->setResponseData(['Error' => "Product was not created"]);
            $this->rest->sendResponse(406);
            return;
        }

        Registry::cache()->flush();

        $this->data['result'] = [
            'status'     => $updateBy ? 'updated' : 'created',
            'product_id' => $product_id,
        ];
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->rest->setResponseData($this->data['result']);
        $this->rest->sendResponse(200);
    }

    /**
     * @param $data
     *
     * @return Product | false
     * @throws Exception
     */
    private function createProduct($data)
    {
        if (!$data['descriptions'] || !current($data['descriptions'])['name']) {
            return false;
        }

        $expected_relations = ['descriptions', 'categories', 'stores', 'tags'];
        $rels = [];
        foreach ($expected_relations as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                $rels[$key] = $data[$key];
                unset($data[$key]);
            }
        }
        //create product
        $product = new Product($data);
        $product->save();

        if (!$product->getKey()) {
            $this->rest->setResponseData(['Error' => "Product cannot be created"]);
            $this->rest->sendResponse(406);
            return null;
        }

        $product->replaceOptions((array)$data['options']);
        //create defined relationships
        $product->updateRelationships($rels);

        //touch category to run recalculation of products count in it
        foreach( (array)$data['category_uuids'] as $uuid ){
            $category = Category::where('uuid', '=', $uuid)->first();
            $category?->touch();
        }

        $product->updateImages($data);
        if($product->errors()){
            $this->log->error(__CLASS__.': '.implode("\n",$product->errors()));
        }

        UrlAlias::replaceKeywords($data['keywords'], $product->getKeyName(), $product->getKey());
        return $product;
    }

    /**
     * @param Product $product
     * @param         $data
     *
     * @return Product
     * @throws Exception
     */
    protected function updateProduct($product, $data)
    {
        $expected_relations = ['descriptions', 'categories', 'stores', 'tags'];
        $rels = [];
        foreach ($expected_relations as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                $rels[$key] = $data[$key];
                unset($data[$key]);
            }
        }
        //get previous categories to update it via listener that calculates products count
        $prev_categories = array_column( $product->categories->toArray(), 'uuid' );

        $product->update($data);

        $product->replaceOptions((array)$data['options']);
        $product->updateRelationships($rels);
        $product->updateImages($data);
        if($product->errors()){
            $this->log->error(__CLASS__.': '.implode("\n",$product->errors()));
        }

        //touch category to run recalculation of products count in it
        foreach( array_merge($prev_categories, (array)$data['category_uuids']) as $uuid ){
            $category = Category::where( [ 'uuid' => $uuid ] )->first();
            $category?->touch();
        }

        UrlAlias::replaceKeywords($data['keywords'], $product->getKeyName(), $product->getKey());
        return $product;
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws AException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    protected function prepareData(array $data)
    {
        //assign product to store we requests
        $data['stores'] = [(int)$this->config->get('config_store_id')];
        //trick for unique sku. null is allowed  for unique index
        if (isset($data['sku'])) {
            $data['sku'] = $data['sku'] === '' ? null : $data['sku'];
        }

        if ($data['category_uuids']) {
            $data['categories'] = (array)Category::select(['category_id'])
                ->whereIn('uuid', (array)$data['category_uuids'])
                ->get()?->pluck('category_id')->toArray();
        } else {
            //if product does not assigned to any category
            $data['categories'] = [];
        }
        if ($data['manufacturer'] && $data['manufacturer']['uuid']) {
            $manufacturer = Manufacturer::where('uuid', '=', $data['manufacturer']['uuid'])
                ->get()?->first();
            if ($manufacturer) {
                $data['manufacturer_id'] = $manufacturer->manufacturer_id;
                unset($data['manufacturer']);
            }
        }

        return $data;
    }

    /**
     * @param array $category
     * @param int $language_id
     *
     * @return int
     * @throws Exception|InvalidArgumentException
     */
    protected function replaceCategories($category, $language_id){
        /** @var Category $exists */
        $exists = Category::getCategoryByName( $category['name'], $category['parent_id']);
        if (!$exists) {
            $new_category_id = Category::addCategory(
                [
                    'parent_id' => $category['parent_id'],
                    'status'    => $category['status'],
                    'sort_order' => $category['sort_order'],
                    'category_description' => [
                        $language_id => [
                            'name'             => html_entity_decode($category['name']),
                            'meta_keywords'    => html_entity_decode($category['meta_keywords']),
                            'meta_description' => html_entity_decode($category['meta_description']),
                            'description'      => html_entity_decode($category['description']),
                        ]
                    ],
                    'category_store' => [$this->config->get('config_store_id')],
                    'keyword' => $category['keyword']

                ]
            );
            if($category['children']){
                $category['children']['parent_id'] = $new_category_id;
                return $this->replaceCategories($category['children'], $language_id);
            }else{
                return $new_category_id;
            }
        }else{
            if($category['children']){
                $category['children']['parent_id'] = $exists->category_id;
                return $this->replaceCategories($category['children'], $language_id);
            }else{
                return $exists->category_id;
            }
        }
    }
}