<?php

namespace Tests\unit\models\order;

use abc\models\order\OrderHistory;
use Illuminate\Validation\ValidationException;
use Tests\unit\ATestCase;

/**
 * Class OrderHistoryModelTest
 */
class OrderHistoryModelTest extends ATestCase
{

    protected function setUp():void
    {
        //init
    }

    public function testValidator()
    {

        $errors = [];
        $order = new OrderHistory();
        try {
            //validate
            $data = [
                'order_id'        => 'fail',
                'notify'          => 'fail',
                'comment'         => [],
                'order_status_id' => 'fail',
            ];
            $order->fill($data);
            $order->validate();
        } catch (ValidationException $e) {
            $errors = $order->errors()['validation'];
            //var_Dump(array_diff(array_keys($data), array_keys($errors) ));
            //var_Dump($errors);
        }

        $this->assertCount(4, $errors);

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

        $this->assertCount(2, $errors);

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

        $this->assertCount(0, $errors);

        //check correct value
        $data = [
            'order_id'        => 2,
            'order_status_id' => 1,
            'notify'          => 1,
            'comment'         => 'test order comment ',
        ];

        $order = new OrderHistory($data);
        $errors = [];
        try {
            $order->validate();
            $order->save();
        } catch (ValidationException $e) {
            $errors = $order->errors()['validation'];
//            var_Dump(array_intersect_key($data, $errors));
//            var_Dump($errors);
        }

        $this->assertCount(0, $errors);
        $order->forceDelete();

    }
}