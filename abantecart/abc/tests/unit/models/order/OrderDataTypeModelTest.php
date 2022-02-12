<?php

namespace Tests\unit\models\order;

use abc\models\order\OrderDataType;
use Illuminate\Validation\ValidationException;
use Tests\unit\ATestCase;

/**
 * Class OrderDataTypeModelTest
 */
class OrderDataTypeModelTest extends ATestCase
{

    protected function setUp():void
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

        $this->assertCount(3, $errors);

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

        $this->assertCount(1, $errors);

        $data = [
            'type_id'     => 3,
            'language_id' => 1,
            'name'        => 'TEST',
        ];

        $orderDataType = new OrderDataType($data);
        $errors = [];
        try {
            $orderDataType->validate();
            $orderDataType->save();
        } catch (ValidationException $e) {
            $errors = $orderDataType->errors()['validation'];
            //var_Dump(array_intersect_key($data, $errors ));
        }

        $this->assertCount(0, $errors);

        if ($orderDataType->type_id) {
            $orderDataType->forceDelete();
        }

    }
}