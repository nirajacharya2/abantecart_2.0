<?php

namespace abc\tests\unit;

use abc\models\locale\Country;
use abc\models\order\OrderStatus;
use Illuminate\Validation\ValidationException;

/**
 * Class OrderStatusModelTest
 */
class OrderStatusModelTest extends ATestCase
{

    protected function setUp()
    {
        //init
    }

    public function testValidator()
    {

        //validate
        $data = [
            'status_text_id' => -0.000000000000000009,
        ];

        $orderStatus = new OrderStatus();
        $errors = [];
        try {
            $orderStatus->validate($data);
        } catch (ValidationException $e) {
            $errors = $orderStatus->errors()['validation'];
            // var_Dump(array_diff(array_keys($data), array_keys($errors) ));
        }
        $this->assertEquals(1, count($errors));

        //validate
        $data = [
            'status_text_id' => 'test_status',
            'display_status' => false,
        ];

        $orderStatus = new OrderStatus($data);
        $errors = [];
        try {
            $orderStatus->validate($data);
            $orderStatus->save();
        } catch (ValidationException $e) {
            $errors = $orderStatus->errors()['validation'];
            var_dump($errors);
            // var_Dump(array_diff(array_keys($data), array_keys($errors) ));
        }
        $this->assertEquals(0, count($errors));

        $orderStatus->forceDelete();
    }
}