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
            'order_id'   => 'fail',
            'product_id' => 'fail',
            'name'       => -0.000000000123232,
            'model'      => -0.000000000123232,
            'sku'        => -0.000000000123232,
            'price'      => 'fail',
            'total'      => 'fail',
            'tax'        => 'fail',
            'quantity'   => 'fail',
            'subtract'   => 'fail',

        ];

        $orderProduct = new OrderProduct();
        $errors = [];
        try {
            $orderProduct->validate($data);
        } catch (ValidationException $e) {
            $errors = $orderProduct->errors()['validation'];
            // var_Dump(array_diff(array_keys($data), array_keys($errors) ));
        }
        $this->assertEquals(10, count($errors));

        //valid data
        $data = [
            'order_id'   => 9,
            'product_id' => 50,
            'name'       => 'test',
            'model'      => 'test',
            'sku'        => 'test',

            'price'    => 1.25,
            'total'    => 2.00,
            'tax'      => 0.75,
            'quantity' => 1,
            'subtract' => true,
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
}