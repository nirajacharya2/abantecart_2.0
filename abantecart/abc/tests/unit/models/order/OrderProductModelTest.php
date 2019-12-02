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
            'order_id'          => 'fail',
            'product_id'        => 'fail',
            'name'              => -0.000000000123232,
            'model'             => -0.000000000123232,
            'sku'               => -0.000000000123232,
            'price'             => 'fail',
            'total'             => 'fail',
            'tax'               => 'fail',
            'quantity'          => 'fail',
            'subtract'          => 'fail',
            'order_status_id'   => 'fail',
            'tax_class_id'      => 'fail',
            'weight'            => 'fail',
            'weight_class_id'   => 'fail',
            'length'            => 'fail',
            'width'             => 'fail',
            'height'            => 'fail',
            'length_class_id'   => 'fail',
            'shipping'          => 'fail',
            'ship_individually' => 'fail',
            'free_shipping'     => 'fail',
            'shipping_price'    => 'fail',
        ];

        $orderProduct = new OrderProduct();
        $errors = [];
        try {
            $orderProduct->validate($data);
        } catch (ValidationException $e) {
            $errors = $orderProduct->errors()['validation'];
             //var_Dump(array_diff(array_keys($errors), array_keys($data) ));
        }
        $this->assertEquals(22, count($errors));

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
            'tax_class_id'      => 1,
            'weight'            => 0.1,
            'weight_class_id'   => 1,
            'length'            => 1,
            'width'             => 1,
            'height'            => 1,
            'length_class_id'   => 1,
            'shipping'          => 1,
            'ship_individually' => 1,
            'free_shipping'     => 0,
            'shipping_price'    => 0.75,
        ];

        $orderProduct = new OrderProduct($data);
        $errors = [];
        try {
            $orderProduct->validate($data);

            //also check if some data absent
            $nullables = [
                'tax_class_id',
                'weight',
                'weight_class_id',
                'length',
                'width',
                'height',
                'length_class_id',
                'shipping',
                'ship_individually',
                'free_shipping',
                'shipping_price'
            ];

            foreach($nullables as $k) {
                unset($data[$k]);
            }
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
        $this->assertEquals(27, count($orderProductOption->toArray()[0]));
    }
}