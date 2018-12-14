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

namespace abantecart\tests;

use abc\core\engine\Registry;
use abc\models\base\Product;
use tests\phpunit\abc\modules\listeners\ATestListener;
use PHPUnit\Framework\Warning;

class BaseModelTest extends ABCTestCase{


    protected function tearDown(){
        //init

    }

    public function testValidationPassed(){

        try {
            $model = new Product();
            $product = $model->find(50);
            $product->fill(
                [
                    'model' => 'valid',
                    'sku'   => null,
                    'location' => 'max:128',
                    'quantity' => 0,
                    'stock_checkout' => '1',
                    'stock_status_id' => 1,
                    'manufacturer_id' => 1,
                    'shipping' => 0,
                    'ship_individually'=> 1,
                    'free_shipping'    => 1,
                    'shipping_price'   => '45.12000',
                ]
            );
            $product->save();
            $result = true;
        }catch(\PDOException $e){
            $result = false;
        }catch(Warning $e){
            $result = false;
        }catch(\Exception $e){
            $result = false;
            $this->fail($e->getMessage());
        }

        $this->assertEquals( true, $result );
    }


    public function testValidationNotPassed(){

        $result = false;

        try {
            $model = new Product();
            $product = $model->find(51);
            $product->fill(
                [
                    'model' => 'invalid',
                    'sku'   => null,
                    'location' => 'max:128000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000',
                    'quantity' => 'a',
                    'shipping_price'   => '$45.12000',
                ]
            );

            $product->save();
            $result = true;
        }catch(\PDOException $e){
            $this->fail($e->getMessage());
        }catch(\Exception $e){
            $error_text = $e->getMessage();
            if(is_int(strpos($error_text,"'validation' =>"))){
                echo $e->getMessage();
                $result = true;
            }else{
                $this->fail($e->getMessage());
            }

        }
        $this->assertEquals( true, $result );
    }

    public function testEventOnSaved(){
        $model = new Product();
        $product = $model->find(50);

        try {
            $product->fill(
                ['model' => 'testmodel',
                 'sku' => '124596788']
            );
            $product->save();
        }catch(\PDOException $e){
            $this->fail($e->getMessage());
        }catch(\Exception $e){
            $this->fail($e->getMessage());
        }

        $this->assertEquals(
            ATestListener::class,
            Registry::getInstance()->get('handler test result')
        );
    }

}