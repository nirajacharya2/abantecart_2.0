<?php

namespace abc\tests\unit;

use abc\models\order\OrderProduct;
use Illuminate\Validation\ValidationException;

/**
 * Class OrderProductModelTest
 */
class OrderProductModelTest extends ATestCase
{

    protected function setUp()
    {
        //init
    }

    public function testValidator()
    {
        //validate
        $data = [
            'order_id'        => 'fail',
            'product_id'      => 'fail',
            'name'            => -0.000000000123232,
            'model'           => -0.000000000123232,
            'sku'             => -0.000000000123232,
            'price'           => 'fail',
            'total'           => 'fail',
            'tax'             => 'fail',
            'quantity'        => 'fail',
            'subtract'        => 'fail',
            'order_status_id' => 'fail',
        ];

        $orderProduct = new OrderProduct();
        $errors = [];
        try {
            $orderProduct->validate($data);
        } catch (ValidationException $e) {
            $errors = $orderProduct->errors()['validation'];
            // var_Dump(array_diff(array_keys($data), array_keys($errors) ));
        }
        $this->assertEquals(11, count($errors));

        //valid data
        $data = [
            'order_id'   => 9,
            'product_id' => 50,
            'name'       => 'test',
            'model'      => 'test',
            'sku'        => 'test',

            'price'           => 1.25,
            'total'           => 2.00,
            'tax'             => 0.75,
            'quantity'        => 1,
            'subtract'        => true,
            'order_status_id' => 1,
        ];

        $orderProduct = new OrderProduct($data);
        $errors = [];
        try {
            $orderProduct->validate($data);
            $orderProduct->save();
        } catch (ValidationException $e) {
            $errors = $orderProduct->errors()['validation'];
            var_Dump(array_diff(array_keys($data), array_keys($errors)));
            var_Dump($errors);
        }
        $this->assertEquals(0, count($errors));
        $orderProduct->forceDelete();
    }

    public function testStaticMethods()
    {
        //test getOrderProductOptions
        $orderProductOption = OrderProduct::getOrderProductOptions(4,18);
        $this->assertEquals(25, count($orderProductOption->toArray()[0]));
    }
}