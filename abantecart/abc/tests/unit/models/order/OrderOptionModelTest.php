<?php

namespace Tests\unit\models\order;

use abc\models\order\OrderOption;
use Illuminate\Validation\ValidationException;
use Tests\unit\ATestCase;

/**
 * Class OrderOptionModelTest
 */
class OrderOptionModelTest extends ATestCase
{

    protected function setUp():void
    {
        //init
    }

    public function testValidator()
    {
        //validate
        $data = [
            'order_id'                => 'fail',
            'order_product_id'        => 'fail',
            'product_option_id'       => 'fail',
            'product_option_value_id' => 'fail',
            'name'                    => -0.000000000123232,
            'sku'                     => -0.000000000123232,
            'value'                   => -0.000000000123232,
            'price'                   => 'fail',
            'prefix'                  => 'fail',
            'settings'                => 'fail',
            'weight'                  => 'fail',
            'weight_type'             => -0.000000000123232,
        ];

        $orderOption = new OrderOption();
        $errors = [];
        try {
            $orderOption->validate($data);
        } catch (ValidationException $e) {
            $errors = $orderOption->errors()['validation'];
            // var_Dump(array_diff(array_keys($data), array_keys($errors) ));
        }
        $this->assertCount(11, $errors);

        //check validation of presence in database
        $data = [
            'order_id'         => 10000000,
            'order_product_id' => 10000000,
            //check another prefix fail
            'prefix'           => -0.000000000123232,
            // fill required junk
            'name'             => 'test',
            'value'            => 'value',
            'weight'           => 0.01,
            'weight_type'      => '%'
        ];

        $orderOption = new OrderOption();
        $errors = [];
        try {
            $orderOption->validate($data);
        } catch (ValidationException $e) {
            $errors = $orderOption->errors()['validation'];
            var_dump($errors);
        }
        $this->assertCount(3, $errors);

        //check validation of nullables
        $data = [
            'sku'              => null,
            'settings'         => null,
            // fill required junk
            'order_id'         => 9,
            'order_product_id' => 6,
            'name'             => 'test',
            'value'            => 'value',
        ];

        $orderOption = new OrderOption();
        $errors = [];
        try {
            $orderOption->validate($data);
        } catch (ValidationException $e) {
            $errors = $orderOption->errors()['validation'];
            //var_Dump($errors);
        }
        $this->assertCount(0, $errors);

        //valid data
        $data = [
            'order_id'          => 9,
            'order_product_id'  => 6,
            'product_option_id' => 304,
            'name'              => 'test',
            'sku'               => 'test',
            'value'             => 'testvalue',
            'price'             => 1.25,
            'prefix'            => '$',
            'settings'          => ['somedata' => 'somevalue'],
            'weight'           => 0.01,
            'weight_type'      => '%'
        ];

        $orderOption = new OrderOption($data);
        $errors = [];
        try {
            $orderOption->validate($data);
            $orderOption->save();
        } catch (ValidationException $e) {
            $errors = $orderOption->errors()['validation'];
//            var_Dump(array_diff(array_keys($data), array_keys($errors)));
            var_dump($errors);
        }
        $this->assertCount(0, $errors);
        $orderOption->forceDelete();
    }
}