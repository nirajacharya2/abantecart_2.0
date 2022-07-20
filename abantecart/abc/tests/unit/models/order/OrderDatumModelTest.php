<?php

namespace Tests\unit\models\order;

use abc\models\order\OrderDatum;
use Illuminate\Validation\ValidationException;
use Tests\unit\ATestCase;

/**
 * Class OrderDatumModelTest
 */
class OrderDatumModelTest extends ATestCase
{

    protected function setUp():void
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

        $this->assertCount(2, $errors);

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

        $this->assertCount(2, $errors);

        $data = [
            'type_id'  => 2,
            'order_id' => 2,
            'data'     => ['someData' => 'someValue'],
        ];

        $orderDatum = new OrderDatum($data);
        $errors = [];
        try {
            $orderDatum->validate();
            $orderDatum->save();
        } catch (ValidationException $e) {
            $errors = $orderDatum->errors()['validation'];
            //var_Dump(array_intersect_key($data, $errors ));
        }

        $this->assertCount(0, $errors);
        $orderDatum->forceDelete();

    }

}