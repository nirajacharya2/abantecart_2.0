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


    public function testValidationPassed(){

        try {
            $product = new Product();
            $product->fill(
                [
                    'model' => 'valid model',
                    'sku'   => null,
                    'location' => 'location-max:128',
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
            $product_id = $product->getKey();
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

        //check audits by requestId
        if($result){
            $audits = $this->db->table('audits')
                ->select('*')
                ->where('request_id', '=', $this->request->getUniqueId())
                ->where('primary_key', '=', $product_id)
                ->get();

            $this->assertEquals( 11, count($audits) );
        }
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
                    'location' => 'max:1280000000000000000000000000000000000000000'
                        .'00000000000000000000000000000000000000000000000000000000000000000000000000000000000',
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