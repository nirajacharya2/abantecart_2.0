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
use abc\models\admin\ModelCatalogCategory;
use abc\models\base\Product;
use abc\modules\events\ABaseEvent;
use abc\core\lib\AException;

/**
 * Class ControllerApiCatalogProduct
 *
 * @package abc\controllers\admin
 *
 * @property ModelCatalogCategory $model_catalog_category
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
        $this->data['request'] = $request;

        $getBy = null;
        if (isset($request['product_id']) && $request['product_id']) {
            $getBy = 'product_id';
        }
        if (isset($request['get_by']) && $request['get_by']) {
            $getBy = $request['get_by'];
        }

        if (!\H::has_value($getBy) || !isset($request[$getBy])) {
            $this->rest->setResponseData(['Error' => $getBy.' is missing']);
            $this->rest->sendResponse(200);
            return null;
        }
        /**
         * @var Product $product
         */
        $product = Product::where([$getBy => $request[$getBy]]);
        if ($product === null) {
            $this->rest->setResponseData(
                ['Error' => "Product with ".$getBy." ".$request[$getBy]." does not exist"]
            );
            $this->rest->sendResponse(200);
            return null;
        }

        $this->data['result'] = [];
        $item = $product->first();
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
     * @throws \Exception
     */
    public function post()
    {
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $request = $this->rest->getRequestParams();

        if(!$request){
            return null;
        }

        try {

            $this->data['request'] = $request;
            $request = $this->prepareData($request);

            //are we updating or creating
            $updateBy = null;
            if (isset($request['product_id']) && $request['product_id']) {
                $updateBy = 'product_id';
            }
            if (isset($request['update_by']) && $request['update_by']) {
                $updateBy = $request['update_by'];
            }

            if ($updateBy) {
                /**
                 * @var Product $product
                 */
                $product = Product::where($updateBy, $request[$updateBy])->first();
                if ($product === null) {
                    $this->rest->setResponseData(
                        ['Error' => "Product with {$updateBy}: {$request[$updateBy]} does not exist"]
                    );
                    $this->rest->sendResponse(200);
                    return null;
                }

                $product = $this->updateProduct($product, $request);
                if (is_object($product)) {
                    \H::event(
                        'abc\controllers\admin\api\catalog\product@update',
                        new ABaseEvent($product->toArray(), ['products'])
                    );
                } else {
                    $product = false;
                }
            } else {
                $product = $this->createProduct($request);
                if (is_object($product)) {
                    \H::event(
                        'abc\controllers\admin\api\catalog\product@create',
                        new ABaseEvent($product->toArray(), ['products'])
                    );
                } else {
                    $product = false;
                }
            }
        } catch (\PDOException $e) {
            $trace = $e->getTraceAsString();
            $this->log->error($e->getMessage());
            $this->log->error($trace);
            $this->rest->setResponseData(['Error' => $e->getMessage()]);
            $this->rest->sendResponse(200);
            return null;
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
     * @throws \Exception
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
        $product = new Product();
        //expand fillable columns for extensions
        if ($this->data['fillable']) {
            $product->addFillable($this->data['fillable']);
        }

        $fills = $product->getFillable();
        foreach ($fills as $fillable) {
            if ($fillable == 'date_available') {
                continue;
            }
            $product->{$fillable} = $data[$fillable];
        }

        $product->save();

        if (!$product || !$product->getKey()) {
            $this->rest->setResponseData(['Error' => "Product cannot be created"]);
            $this->rest->sendResponse(200);
            return null;
        }

        $product->replaceOptions((array)$data['options']);
        //create defined relationships
        $product->updateRelationships($rels);
        $product->updateImages($data);
        $product->replaceKeywords($data['keywords']);

        return $product;
    }

    /**
     * @param Product $product
     * @param         $data
     *
     * @return mixed
     * @throws \Exception
     */
    private function updateProduct($product, $data)
    {

        $expected_relations = ['descriptions', 'categories', 'stores', 'tags'];
        $rels = [];
        foreach ($expected_relations as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                $rels[$key] = $data[$key];
                unset($data[$key]);
            }
        }

        //expand fillable columns for extensions
        if ($this->data['fillable']) {
            $product->addFillable($this->data['fillable']);
        }

        $fills = $product->getFillable();
$upd_array = [];
        foreach ($fills as $fillable) {
            if (isset($data[$fillable])) {
                $product->{$fillable} = urldecode($data[$fillable]);
$upd_array[$fillable] = urldecode($data[$fillable]);
            }
        }

if($upd_array) {
    $this->db->table('products')->where('product_id', $product->product_id)->update($upd_array);
}

        //$product->save();
        $product->replaceOptions((array)$data['options']);
        $product->updateRelationships($rels);
        $product->updateImages($data);
        $product->replaceKeywords($data['keywords']);
        return $product;
    }

    /**
     * @param array $data
     *
     * @return mixed
     * @throws AException
     */
    protected function prepareData($data)
    {
        //assign product to store we requests
        $data['stores'] = [$this->config->get('config_store_id')];

        if($data['categories']) {
            $categories = [];
            foreach($data['categories'] as $category_branch) {
                $categories[] = $this->processCategoryTree($category_branch);
            }
            $data['categories'] = $categories;
        }

        return $data;
    }

    protected function processCategoryTree(array $category_tree){
        $this->loadModel('catalog/category');
        foreach($category_tree as $lang_code => $category){
            $language_id = $this->language->getLanguageIdByCode($lang_code);
            //Note: start from parent category!
            if($category_tree['parent_id']!=0){
                throw new AException(
                    'Data integrity check error: Category Tree must start from root category. Parent_id must be 0!'
                );
            }
            //note: only one language yet
            return $this->replaceCategories($category, $language_id);
        }
    }

    protected function replaceCategories($category, $language_id){
        $exists = $this->getCategoryByName($category['name'], $category['parent_id']);
        if (!$exists) {
            $new_category_id = $this->model_catalog_category->addCategory(
                [
                    'parent_id' => $category['parent_id'],
                    'status'    => $category['status'],
                    'sort_order' => $category['sort_order'],
                    'category_description' => [
                        $language_id => [
                            'name' => html_entity_decode($category['name']),
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
                $category['children']['parent_id'] = $exists['category_id'];
                return $this->replaceCategories($category['children'], $language_id);
            }else{
                return $exists['category_id'];
            }
        }
    }

    public function getCategoryByName($name, $parent_id)
    {
        $parent_id = (int)$parent_id;
        $result = $this->db->query(
            "SELECT cd.*, c.*
            FROM ".$this->db->table_name("category_descriptions")." cd
            LEFT JOIN ".$this->db->table_name("categories")." c
                 ON (c.category_id = cd.category_id)
            WHERE c.parent_id = '".$parent_id."' AND LOWER(name) = '".mb_strtolower($name)."'"
        );

        return $result->row;
    }
}
