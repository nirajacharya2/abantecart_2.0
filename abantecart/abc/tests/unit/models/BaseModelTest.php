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

namespace unit\models;
use abc\core\ABC;
use abc\tests\unit\ATestCase;
use abc\models\catalog\Product;
use abc\tests\unit\modules\listeners\ATestListener;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Warning;

class BaseModelTest extends ATestCase
{

    public function testValidationPassed()
    {
        $productId = null;
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
            'model'               => 'valid model',
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
            'location'          => 'location-max:128',
            'keyword'           => '',
            'date_available'    => '2013-08-29 14:35:30',
            'sort_order'        => '1',
            'shipping'          => '1',
            'free_shipping'     => '0',
            'ship_individually' => '0',
            'shipping_price'    => '0',
            'length'            => '0.00',
            'width'             => '0.00',
            'height'            => '0.00',
            'length_class_id'   => null,
            'weight'            => '75.00',
            'weight_class_id'   => '2',
        ];
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

      return $productId;
    }

    public function testValidationNotPassed()
    {

        $result = false;

        try {
            $product = new Product(
                [
                    'model'          => 'invalid',
                    'sku'            => null,
                    'location'       => 'max:1280000000000000000000000000000000000000000'
                        .'00000000000000000000000000000000000000000000000000000000000000000000000000000000000',
                    'quantity'       => 'a',
                    'shipping_price' => '$45.12000',
                ]
            );

            $product->save();
            $result = true;
        } catch (\PDOException $e) {
            $this->fail($e->getMessage());
        } catch (ValidationException $e){
            $error_text = $e->getMessage();
            $result = true;
        } catch (\Exception $e) {
            $error_text = $e->getMessage();
            if (is_int(strpos($error_text, "'validation' =>"))) {
                echo $e->getMessage();
                $result = true;
            } else {
                $this->fail($e->getMessage());
            }

        }
        $this->assertEquals(true, $result);
    }

}
