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

class BaseModelTest extends ABCTestCase{


    protected function tearDown(){
        //init

    }

    public function testEventOnSaved(){

        try {
            $model = new Product();
            $product = $model->find(50)->update(['model'=>'455544']);
        }catch(\PDOException $e){
            echo $e->getTraceAsString();
        }catch(\Exception $e){
            echo $e->getTraceAsString();
        }

        $this->assertEquals(
            ATestListener::class,
            Registry::getInstance()->get('handler test result')
        );
    }

}