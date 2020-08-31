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

use abc\core\engine\Registry;
use abc\models\catalog\Product;
use abc\models\catalog\ProductDescription;
use abc\models\catalog\UrlAlias;
use abc\tests\unit\ATestCase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Warning;

class ProductModelTest extends ATestCase
{
    public function testGetAllData()
    {
        //NOTE: Product ID 64 have all relations.
        // Do not delete them before test!

        Product::setCurrentLanguageID(1);
        $product = Product::find(64);
        $data = $product->getAllData();
        $rels = Product::getRelationships('HasMany', 'HasOne', 'belongsToMany');
        $rels = array_keys($rels);
        foreach ($rels as $rel) {
            $rel = Str::snake($rel);
            //var_dump($rel.": ".count($data[$rel]));
            $this->assertGreaterThan(0, count($data[$rel]));
        }
    }

//    public function testCopyProduct()
//    {
//        Product::setCurrentLanguageID(1);
//        $product = Product::find(64);
//        $product_id = $product->copyProduct();
//        var_dump($product_id);
//exit;
//    }

    public function testValidator()
    {

        //validate new product
        $product = new Product();
        $errors = [];
        try {
            $data = [
                'product_id'        => -0.1,
                'uuid'              => -0.00000000021,
                'model'             => -0.00000000021,
                'sku'               => -1,
                'location'          => -1,
                'quantity'          => 'fail',
                'stock_checkout'    => 'fail',
                'stock_status_id'   => 'fail',
                'manufacturer_id'   => 9999,
                'shipping'          => 'fail',
                'ship_individually' => 'fail',
                'free_shipping'     => 'fail',
                'shipping_price'    => 'fail',
                'price'             => 'fail',
                'tax_class_id'      => 'fail',
                'weight'            => 'fail',
                'weight_class_id'   => 99999,
                'length'            => 'fail',
                'width'             => 'fail',
                'height'            => 'fail',
                'length_class_id'   => 'fail',
                'status'            => 'fail',
                'viewed'            => 'fail',
                'sort_order'        => 'fail',
                'call_to_order'     => -0.00000000021,
                'cost'              => 'fail',
                'subtract'          => 'fail',
                'minimum'           => 'fail',
                'maximum'           => 'fail',
                'product_type_id'   => 'fail',
                'settings'          => -0.00000000021,
            ];
            $product->validate($data);
        } catch (ValidationException $e) {
            $errors = $product->errors()['validation'];
            //var_Dump(var_dump(array_diff(array_keys($data),array_keys($errors))));
        }

        $this->assertEquals(30, count($errors));

        //validate correct data
        $product = new Product();
        $errors = [];
        try {
            $data = [
                'uuid'              => 'sssss',
                'model'             => 'tesmodeltest',
                'sku'               => 'testskutest',
                'location'          => 'somewhere',
                'quantity'          => 10,
                'stock_checkout'    => '0',
                'stock_status_id'   => 1,
                'manufacturer_id'   => 11,
                'shipping'          => false,
                'ship_individually' => false,
                'free_shipping'     => '0',
                'shipping_price'    => 0.0,
                'price'             => 1.00,
                'tax_class_id'      => 1,
                'date_available'    => date('Y-m-d H:i:s'),
                'weight'            => 0.0,
                'weight_class_id'   => 1,
                'length'            => 0.0,
                'width'             => 0.0,
                'height'            => 0.0,
                'length_class_id'   => 1,
                'status'            => 1,
                'viewed'            => 0,
                'sort_order'        => 15,
                'call_to_order'     => 0,
                'cost'              => 0.95,
                'subtract'          => 0,
                'minimum'           => 1,
                'maximum'           => 150,
                'product_type_id'   => null,
                'settings'          => '',
            ];
            $product->validate($data);
        } catch (ValidationException $e) {
            $errors = $product->errors()['validation'];
            var_Dump($errors);
        }
        $this->assertEquals(0, count($errors));

    }

    /**
     * @return int
     */
    public function testCreateProduct()
    {
        $urlAliases = UrlAlias::where('keyword', 'like', 'test-seo-keyword%')->forceDelete();
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
            'keyword'             => 'test-seo-keyword',
            'date_available'      => '2013-08-29 14:35:30',
            'sort_order'          => '1',
            'shipping'            => '1',
            'free_shipping'       => '0',
            'ship_individually'   => '0',
            'shipping_price'      => '0',
            'length'              => '0.00',
            'width'               => '0.00',
            'height'              => '0.00',
            'length_class_id'     => '1',
            'weight'              => '75.00',
            'weight_class_id'     => '2',
        ];
        $productId = null;
        try {
            $product = Product::createProduct($arProduct);
            $productId = $product->getKey();
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
        $this->assertIsInt($product->hasAnyStock());
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
            'featured'            => 0,
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
            Product::setCurrentLanguageID(1);
            Product::updateProduct($productId, $arProductData);
        } catch (\PDOException $e) {
            $this->fail($e->getMessage());
        } catch (Warning $e) {
            $this->fail($e->getMessage());
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }

        $product = Product::find($productId);
        $this->assertEquals(false, $product->featured);

    }

    /**
     * @depends testCreateProduct
     *
     * @param int $productId
     */
    public function testTouchProduct(int $productId)
    {
        $product = Product::find($productId);
        sleep(1);
        $old_date = (string)$product->date_modified;
        try {
            /** @var ProductDescription $pd */
            $pd = ProductDescription::where('product_id', '=', $productId)->first();
            $pd->touch();
        } catch (\PDOException $e) {
            $this->fail($e->getMessage());
        } catch (Warning $e) {
            $this->fail($e->getMessage());
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }
        $product->refresh();
        $this->assertNotEquals((string)$product->date_modified, $old_date);

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
        $this->assertEquals(true, $result);
    }

    public function testStaticMethods()
    {
        $db = Registry::db();
        //test getOrderProductOptionsWithValues
        /* $productOptions = Product::getProductOptionsWithValues(80);
         $this->assertEquals(1, count($productOptions));
         $this->assertEquals(11, count($productOptions[0]['description']));
         $this->assertEquals(3, count($productOptions[0]['values']));*/

        $related_ids = [50, 50, 50, 51];
        Product::relateProducts($related_ids);

        $result = $db->query("SELECT product_id, related_id FROM ".$db->table_name("products_related")
            ." WHERE product_id=50");
        $this->assertEquals(1, $result->num_rows);
        $result = $db->query("SELECT product_id, related_id FROM ".$db->table_name("products_related")
            ." WHERE product_id=51");
        $this->assertEquals(1, $result->num_rows);

        $product = $product = Product::find(64);
        $options = $product->getProductOptions();

        $this->assertEquals($options[0]['product_option_id'], 314);
        $this->assertEquals($options[0]['descriptions'][0]['name'], 'Fragrance Size');
        $this->assertEquals($options[0]['language'][1]['name'], 'Fragrance Size');
        $this->assertEquals($options[0]['product_option_value'][0]['descriptions'][0]['name'], '1.0 oz');
        $this->assertEquals($options[0]['product_option_value'][0]['language'][1]['name'], '1.0 oz');

    }

}