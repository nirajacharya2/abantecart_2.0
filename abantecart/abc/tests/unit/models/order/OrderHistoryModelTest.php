<?php

namespace abc\tests\unit;

use abc\models\order\OrderHistory;
use Illuminate\Validation\ValidationException;

/**
 * Class OrderHistoryModelTest
 */
class OrderHistoryModelTest extends ATestCase
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
            'notify'          => 'fail',
            'comment'         => -0.000000000123232,
            'order_status_id' => 'fail',
        ];
        $order = new OrderHistory($data);
        $errors = [];
        try {
            $order->validate();
        } catch (ValidationException $e) {
            $errors = $order->errors()['validation'];
            // var_Dump(array_diff(array_keys($data), array_keys($errors) ));
        }

        $this->assertEquals(4, count($errors));

        //check validation of presence in database
        $data = [
            'order_id'        => 1500,
            'order_status_id' => 1500,
        ];
        $order = new OrderHistory($data);
        $errors = [];
        try {
            $order->validate();
        } catch (ValidationException $e) {
            $errors = $order->errors()['validation'];
            // var_Dump(array_diff(array_keys($data), array_keys($errors) ));
        }

        $this->assertEquals(2, count($errors));

        //check validation of presence in database
        $data = [
            'order_id'        => 2,
            'order_status_id' => 1,
        ];
        $order = new OrderHistory($data);
        $errors = [];
        try {
            $order->validate();
        } catch (ValidationException $e) {
            $errors = $order->errors()['validation'];
            // var_Dump(array_diff(array_keys($data), array_keys($errors) ));
        }

        $this->assertEquals(0, count($errors));

        //check correct value
        $data = [
            'order_id'        => 2,
            'order_status_id' => 1,
            'notify'          => 1,
            'comment'         => 'test order comment ',
        ];

        $order = new OrderHistory($data);
        $errors = [];
        $order_id = null;
        try {
            $order->validate();
            $order->save();
        } catch (ValidationException $e) {
            $errors = $order->errors()['validation'];
            var_Dump(array_intersect_key($data, $errors));
            var_Dump($errors);
        }

        $this->assertEquals(0, count($errors));
        $order->forceDelete();

    }
}