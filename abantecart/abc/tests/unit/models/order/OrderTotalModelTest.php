<?php

namespace abc\tests\unit;

use abc\models\order\OrderTotal;
use Illuminate\Validation\ValidationException;

/**
 * Class OrderTotalModelTest
 */
class OrderTotalModelTest extends ATestCase
{

    protected function setUp():void
    {
        //init
    }

    public function testValidator()
    {
        //validate
        $data = [
            'order_id'   => 'fail',
            'title'      => 0.000000000009,
            'text'       => 0.000000000009,
            'value'      => 'fail',
            'data'       => 0.000000000009,
            'sort_order' => 'fail',
            'type'       => 0.000000000009,
            'key'        => 0.000000000009,
        ];

        $orderStatus = new OrderTotal();
        $errors = [];
        try {
            $orderStatus->validate($data);
        } catch (ValidationException $e) {
            $errors = $orderStatus->errors()['validation'];
            // var_Dump(array_diff(array_keys($data), array_keys($errors) ));
        }
        $this->assertEquals(7, count($errors));

        //validate
        $data = [
            'order_id'   => 2,
            'title'      => 'Test Total:',
            'text'       => '$0.01',
            'value'      => 0.01,
            'data'       => ['some-data' => 'some_value'],
            'sort_order' => 1,
            'type'       => 'unittest',
            'key'        => 'test_total',
        ];

        $orderStatus = new OrderTotal($data);
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