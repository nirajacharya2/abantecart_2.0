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

namespace abc\models\admin;

use abc\models\base\ProductDescription;

class Product extends \abc\models\base\Product
{

    public static function createProduct(array $product_data)
    {
        $product = new Product($product_data);
        $product->save();
        $productId = $product->product_id;
        if ($productId) {
            $description = new ProductDescription($product_data['product_description']);
            $product->descriptions()->save($description);

            self::updateProductLinks($productId, $product_data);
            return $productId;
        }
    }

    /**
     * @param int   $product_id
     * @param array $product_data
     * @param int   $language_id
     */
    public static function updateProduct(int $product_id, array $product_data, int $language_id)
    {
        $product = Product::find($product_id);
        $product->update($product_data);
        $product->descriptions()->where('language_id', $language_id)->update($product_data['product_description']);

        self::updateProductLinks($product_id, $product_data);
    }

    public static function updateProductLinks(int $product_id, array $product_data)
    {
        $product = Product::find($product_id);

        if (isset($product_data['product_category'])) {
            $product->categories()->sync($product_data['product_category']);
        }

        if (isset($product_data['product_store'])) {
            $product->stores()->sync($product_data['product_store']);
        }

        if (isset($product_data['product_download'])) {
            $product->downloads()->sync($product_data['product_download']);
        }

        if (isset($product_data['product_related'])) {
            $product->related()->sync($product_data['product_related']);
        }
    }


}