<?php

namespace Tests\unit\models\order;

use abc\models\order\OrderStatus;
use abc\models\order\OrderStatusDescription;
use Illuminate\Validation\ValidationException;
use Tests\unit\ATestCase;

/**
 * Class OrderStatusDescriptionModelTest
 */
class OrderStatusDescriptionModelTest extends ATestCase
{

    protected function setUp():void
    {
        //init
    }

    public function testValidator()
    {
        //validate
        $data = [
            'order_status_id' => 'fail',
            'language_id'     => 'fail',
            'name'            => -0.900000000000000000000009,
        ];

        $orderStatusDescription = new OrderStatusDescription();
        $errors = [];
        try {
            $orderStatusDescription->validate($data);
        } catch (ValidationException $e) {
            $errors = $orderStatusDescription->errors()['validation'];
            //var_dump($errors);
            // var_Dump(array_diff(array_keys($data), array_keys($errors) ));
        }
        $this->assertCount(3, $errors);

        //validate
        $orderStatus = new OrderStatus(['status_text_id' => 'test_status']);
        $orderStatus->save();

        $data = [
            'order_status_id' => $orderStatus->order_status_id,
            'language_id'     => 1,
            'name'            => 'Test order status description',
        ];

        $orderStatusDescription = new OrderStatusDescription($data);
        $errors = [];
        try {
            $orderStatusDescription->validate($data);
            $orderStatusDescription->save();
        } catch (ValidationException $e) {
            $errors = $orderStatusDescription->errors()['validation'];
            var_dump($errors);
            // var_Dump(array_diff(array_keys($data), array_keys($errors) ));
        }
        $this->assertCount(0, $errors);

        $orderStatusDescription->forceDelete();
        $orderStatus->forceDelete();
    }
}