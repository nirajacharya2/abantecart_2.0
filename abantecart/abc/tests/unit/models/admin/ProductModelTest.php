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

namespace abc\tests\unit\models\admin;

use abc\models\admin\Product;
use abc\tests\unit\ATestCase;
use PHPUnit\Framework\Warning;

class ProductModelTest extends ATestCase
{
    /**
     * @return int
     */
    public function testCreateProduct()
    {
        $arProduct = [
            'status'              => '1',
            'featured'            => '1',
            'product_description' =>
                [
                    'name'             => 'Test product',
                    'blurb'            => 'Test blurb',
                    'description'      => 'Test description',
                    'meta_keywords'    => '',
                    'meta_description' => '',
                    'language_id'      => 1,
                ],
            'product_tags'        => 'cheeks,makeup',
            'product_category'    =>
                [
                    0 => '40',
                ],
            'product_store'       =>
                [
                    0 => '0',
                ],
            'manufacturer_id'     => '11',
            'model'               => 'Test Model',
            'call_to_order'       => '0',
            'price'               => '29.5000',
            'cost'                => '22',
            'tax_class_id'        => '1',
            'subtract'            => '0',
            'quantity'            => '99',
            'minimum'             => '1',
            'maximum'             => '0',
            'stock_checkout'      => '',
            'stock_status_id'     => '1',
            'sku'                 => '124596788',
            'location'            => '',
            'keyword'             => '',
            'date_available'      => '2013-08-29 14:35:30',
            'sort_order'          => '1',
            'shipping'            => '1',
            'free_shipping'       => '0',
            'ship_individually'   => '0',
            'shipping_price'      => '0',
            'length'              => '0.00',
            'width'               => '0.00',
            'height'              => '0.00',
            'length_class_id'     => '0',
            'weight'              => '75.00',
            'weight_class_id'     => '2',
        ];
        try {
            $productId = Product::createProduct($arProduct);
        } catch (\PDOException $e) {
            $this->fail($e->getMessage());
        } catch (Warning $e) {
            $this->fail($e->getMessage());
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }

        $this->assertIsInt($productId);
        return $productId;
    }

    /**
     * @depends testCreateProduct
     *
     * @param int $productId
     */
    public function testReadProduct(int $productId)
    {
        $product = Product::find($productId);
        $this->assertEquals(1, $product->status);
    }

    /**
     * @depends testCreateProduct
     *
     * @param int $productId
     */
    public function testUpdateProduct(int $productId)
    {
        $arProductData = [
            'featured'            => '0',
            'product_description' =>
                [
                    'name'             => 'Test update product',
                    'blurb'            => 'Test update blurb',
                    'description'      => 'Test update description',
                    'meta_keywords'    => '',
                    'meta_description' => '',
                    'language_id'      => 1,
                ],
        ];
        try {
            Product::updateProduct($productId, $arProductData, 1);
        } catch (\PDOException $e) {
            $this->fail($e->getMessage());
        } catch (Warning $e) {
            $this->fail($e->getMessage());
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }


        $product = Product::find($productId);
        $this->assertEquals(0, $product->featured);

    }

    /**
     * @depends testCreateProduct
     *
     * @param $productId
     */
    public function testDeleteProduct(int $productId)
    {
        try {
        //    $result = Product::destroy($productId);
        } catch (\PDOException $e) {
            $this->fail($e->getMessage());
            $result = false;
        } catch (Warning $e) {
            $this->fail($e->getMessage());
            $result = false;
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
            $result = false;
        }

        //$this->assertEquals(0, $result);
    }

}