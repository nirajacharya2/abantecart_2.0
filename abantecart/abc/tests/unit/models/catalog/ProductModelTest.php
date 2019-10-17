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

namespace abc\tests\unit\models\catalog;

use abc\models\catalog\Product;
use abc\tests\unit\ATestCase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Warning;

class ProductModelTest extends ATestCase
{
    public function testValidator()
    {
        //validate new product
        $product = new Product();
        $errors = [];
        try{
            $data = [
                'product_id'          => -0.1,
                'uuid'                => -0.00000000021,
                'model'               => -0.00000000021,
                'sku'                 => -1,
                'location'            => -1,
                'quantity'            => 'fail',
                'stock_checkout'      => 'fail',
                'stock_status_id'     => 'fail',
                'manufacturer_id'     => 9999,
                'shipping'            => 'fail',
                'ship_individually'   => 'fail',
                'free_shipping'       => 'fail',
                'shipping_price'      => 'fail',
                'price'               => 'fail',
                'tax_class_id'        => 'fail',
                'weight'              => 'fail',
                'weight_class_id'     => 99999,
                'length'              => 'fail',
                'width'               => 'fail',
                'height'              => 'fail',
                'length_class_id'     => 'fail',
                'status'              => 'fail',
                'viewed'              => 'fail',
                'sort_order'          => 'fail',
                'call_to_order'       => -0.00000000021,
                'cost'                => 'fail',
                'subtract'            => 'fail',
                'minimum'             => 'fail',
                'maximum'             => 'fail',
                'product_type_id'     => 'fail',
                'settings'            => -0.00000000021,
            ];
            $product->validate($data);
        }catch(ValidationException $e){
            $errors = $product->errors()['validation'];
            //var_Dump(var_dump(array_diff(array_keys($data),array_keys($errors))));
        }

        $this->assertEquals(30, count($errors));

        //validate correct data
        $product = new Product();
        $errors = [];
        try{
            $data = [
                'uuid'                => 'sssss',
                'model'               => 'tesmodeltest',
                'sku'                 => 'testskutest',
                'location'            => 'somewhere',
                'quantity'            => 10,
                'stock_checkout'      => '0',
                'stock_status_id'     => 1,
                'manufacturer_id'     => 11,
                'shipping'            => false,
                'ship_individually'   => false,
                'free_shipping'       => '0',
                'shipping_price'      => 0.0,
                'price'               => 1.00,
                'tax_class_id'        => 2,
                'date_available'      => date('Y-m-d H:i:s'),
                'weight'              => 0.0,
                'weight_class_id'     => 1,
                'length'              => 0.0,
                'width'               => 0.0,
                'height'              => 0.0,
                'length_class_id'     => 1,
                'status'              => 1,
                'viewed'              => 0,
                'sort_order'          => 15,
                'call_to_order'       => 0,
                'cost'                => 0.95,
                'subtract'            => 0,
                'minimum'             => 1,
                'maximum'             => 0,
                'product_type_id'     => null,
                'settings'            => '',
            ];
            $product->validate();
        }catch(ValidationException $e){
            $errors = $product->errors()['validation'];

        }
var_Dump($errors);
        $this->assertEquals(0, count($errors));

    }




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
            'call_to_order'     => '0',
            'price'             => '29.5000',
            'cost'              => '22',
            'tax_class_id'      => '1',
            'subtract'          => '0',
            'quantity'          => '99',
            'minimum'           => '1',
            'maximum'           => '0',
            'stock_checkout'    => '',
            'stock_status_id'   => '1',
            'sku'               => '124596788',
            'location'          => '',
            'keyword'           => 'test-seo-keyword',
            'date_available'    => '2013-08-29 14:35:30',
            'sort_order'        => '1',
            'shipping'          => '1',
            'free_shipping'     => '0',
            'ship_individually' => '0',
            'shipping_price'    => '0',
            'length'            => '0.00',
            'width'             => '0.00',
            'height'            => '0.00',
            'length_class_id'   => '1',
            'weight'            => '75.00',
            'weight_class_id'   => '2',
        ];
        $productId = null;
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

        $product_info = Product::getProductInfo((int)$productId);

        $this->assertEquals($arProduct['keyword'], $product_info['keyword']);
        $this->assertEquals($arProduct['product_description']['name'], $product_info['name']);
        return $productId;
    }

    public function testHasAnyStock()
    {
        $product = Product::find(57);
        $this->assertEquals(true, $product->hasAnyStock());
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
            'product_category'    =>
                [
                    0 => '40',
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
            $result = Product::destroy($productId);
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

        $this->assertEquals(1, $result);
    }

    /* public function testStaticMethods()
     {
         //test getOrderProductOptionsWithValues
         $productOptions = Product::getProductOptionsWithValues(80);
         $this->assertEquals(1, count($productOptions));
         $this->assertEquals(11, count($productOptions[0]['description']));
         $this->assertEquals(3, count($productOptions[0]['values']));
     }*/

}