<?php

namespace abc\tests\unit;

use abc\models\order\OrderDataType;
use Illuminate\Validation\ValidationException;

/**
 * Class OrderDataTypeModelTest
 */
class OrderDataTypeModelTest extends ATestCase
{

    protected function setUp()
    {
        //init
    }

    public function testValidator()
    {
        //validate
        $data = [
            'type_id'     => 'fail',
            'language_id' => -0.000000000123232,
            'name'        => -0.000000000123232,
        ];
        $orderDataType = new OrderDataType($data);
        $errors = [];
        try {
            $orderDataType->validate();
        } catch (ValidationException $e) {
            $errors = $orderDataType->errors()['validation'];
            // var_Dump(array_diff(array_keys($data), array_keys($errors) ));
        }

        $this->assertEquals(3, count($errors));

        //check validation of presence in database
        $data = [
            'type_id'     => 3,
            'language_id' => 1500,
            'name'        => 'test',
        ];
        $orderDataType = new OrderDataType($data);
        $errors = [];
        try {
            $orderDataType->validate();
        } catch (ValidationException $e) {
            $errors = $orderDataType->errors()['validation'];
            //var_Dump(array_diff(array_keys($data), array_keys($errors) ));
        }

        $this->assertEquals(1, count($errors));

        $data = [
            'type_id'     => 3,
            'language_id' => 1,
            'name'        => 'TEST',
        ];

        $orderDataType = new OrderDataType($data);
        $errors = [];
        $order_id = null;
        try {
            $orderDataType->validate();
            $orderDataType->save();
        } catch (ValidationException $e) {
            $errors = $orderDataType->errors()['validation'];
            //var_Dump(array_intersect_key($data, $errors ));
        }

        $this->assertEquals(0, count($errors));

        if ($orderDataType->type_id) {
            $orderDataType->forceDelete();
        }

    }
}