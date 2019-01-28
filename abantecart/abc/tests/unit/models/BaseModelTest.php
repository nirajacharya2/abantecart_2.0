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
use abc\models\base\Product;
use abc\tests\unit\modules\listeners\ATestListener;
use PHPUnit\Framework\Warning;

class BaseModelTest extends ATestCase
{

    public function testValidationPassed()
    {
        try {
            $product = new Product();
            $product->fill(
                [
                    'model'             => 'valid model',
                    'sku'               => null,
                    'location'          => 'location-max:128',
                    'quantity'          => 0,
                    'stock_checkout'    => '1',
                    'stock_status_id'   => 1,
                    'manufacturer_id'   => 1,
                    'shipping'          => 0,
                    'ship_individually' => 1,
                    'free_shipping'     => 1,
                    'shipping_price'    => '45.12000',
                ]
            );
            $product->save();
            $product_id = $product->getKey();
            $result = true;
        } catch (\PDOException $e) {
            $result = false;
            $this->fail($e->getMessage());
        } catch (Warning $e) {
            $result = false;
            $this->fail($e->getMessage());
        } catch (\Exception $e) {
            $result = false;
            $this->fail($e->getMessage());
        }

        $this->assertEquals(true, $result);

        //check audits by requestId
        if ($result) {
            $audits = $this->db->table('audits')
                ->select('*')
                ->where('request_id', '=', $this->request->getUniqueId())
                ->where('auditable_id', '=', $product_id)
                ->get();

            $this->assertEquals(11, count($audits));
        }
        return $product_id;
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

    /**
     * @depends testValidationPassed
     */
    public function testEventOnSaved($productId)
    {
        $model = new Product();
        $product = $model->find($productId);

        try {
            $product->fill(
                [
                    'model' => 'testmodel',
                    'sku'   => '124596788',
                ]
            );
            $product->save();
        } catch (\PDOException $e) {
            $this->fail($e->getMessage());
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }

        $this->assertEquals(
            ATestListener::class,
            $this->registry->get('handler test result')
        );
    }

    /**
     * @depends testValidationPassed
     */
    public function testSoftDelete($productId)
    {
        $model = new Product();
        $product = $model->find($productId);
        $result = false;

        if($product) {
            $product->delete();
            Product::onlyTrashed()->where('product_id', $productId)->restore();
            try {
                $product->get(['date_deleted']);
                $result = true;
            } catch (\PDOException $e) {}
        }
        $this->assertEquals($result, true);

        if($result) {
            //test force deleting
            $env = ABC::env('MODEL');
            $env['FORCE_DELETING'][Product::class] = true;
            ABC::env('MODEL', $env, true);
            $model = new Product();
            $product = $model->find($productId);
            $product->delete();
            $exists = Product::onlyTrashed()->where('product_id', $productId)->exists();
            $this->assertEquals($exists, false);
        }

    }

}