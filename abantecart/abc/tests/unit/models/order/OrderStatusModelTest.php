<?php

namespace Tests\unit\models\order;

use abc\models\locale\Country;
use abc\models\order\OrderStatus;
use Illuminate\Validation\ValidationException;
use Tests\unit\ATestCase;

/**
 * Class OrderStatusModelTest
 */
class OrderStatusModelTest extends ATestCase
{

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
        $this->assertCount(1, $errors);

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
        $this->assertCount(0, $errors);

        $orderStatus->forceDelete();
    }
}