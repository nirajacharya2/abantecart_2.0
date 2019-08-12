<?php

namespace abc\tests\unit;

use abc\models\order\OrderDatum;
use Illuminate\Validation\ValidationException;

/**
 * Class OrderDatumModelTest
 */
class OrderDatumModelTest extends ATestCase
{

    protected function setUp()
    {
        //init
    }

    public function testValidator()
    {
        //validate
        $data = [
            'type_id'  => 'fail',
            'order_id' => -0.000000000123232,
            'data'     => 'fail',
        ];
        $orderDatum = new OrderDatum($data);
        $errors = [];
        try {
            $orderDatum->validate();
        } catch (ValidationException $e) {
            $errors = $orderDatum->errors()['validation'];
            //  var_Dump($errors);
        }

        $this->assertEquals(2, count($errors));

        //check validation of presence in database
        $data = [
            'type_id'     => 122,
            'language_id' => 1500,
        ];
        $orderDatum = new OrderDatum($data);
        $errors = [];
        try {
            $orderDatum->validate();
        } catch (ValidationException $e) {
            $errors = $orderDatum->errors()['validation'];
            //var_Dump(array_diff(array_keys($data), array_keys($errors) ));
        }

        $this->assertEquals(2, count($errors));

        $data = [
            'type_id'  => 2,
            'order_id' => 2,
            'data'     => ['someData' => 'someValue'],
        ];

        $orderDatum = new OrderDatum($data);
        $errors = [];
        $order_id = null;
        try {
            $orderDatum->validate();
            $orderDatum->save();
        } catch (ValidationException $e) {
            $errors = $orderDatum->errors()['validation'];
            //var_Dump(array_intersect_key($data, $errors ));
        }

        $this->assertEquals(0, count($errors));
        $orderDatum->forceDelete();

    }

}