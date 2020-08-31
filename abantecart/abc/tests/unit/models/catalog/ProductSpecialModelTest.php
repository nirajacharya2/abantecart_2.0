<?php

namespace abc\tests\unit;

use abc\models\catalog\ProductSpecial;
use Illuminate\Validation\ValidationException;

/**
 * Class ProductSpecialModelTest
 */
class ProductSpecialModelTest extends ATestCase
{

    protected function setUp()
    {
        //init
    }

    public function testValidator()
    {
        $productSpecial = new ProductSpecial();
        $errors = [];
        try {
            $data = [
                'product_id'        => false,
                'customer_group_id' => false,
                'priority'          => false,
                'price'             => false,
                'date_start'        => '0000-00-00-000',
                'date_end'          => '0000-00-00-000',
            ];
            $productSpecial->validate($data);
        } catch (ValidationException $e) {
            $errors = $productSpecial->errors()['validation'];
        }

        $this->assertEquals(6, count($errors));

        $errors = [];
        try {
            $data = [
                'product_id'        => 50,
                'customer_group_id' => 1,
                'priority'          => 1,
                'price'             => 10.12,
                'date_start'        => date('Y-m-d H:i:s'),
                'date_end'          => date('Y-m-d H:i:s'),
            ];
            $productSpecial->validate($data);
        } catch (ValidationException $e) {
            $errors = $productSpecial->errors()['validation'];
        }
        $this->assertEquals(0, count($errors));

        $errors = [];
        try {
            $data = [
                'product_id'        => 50,
                'customer_group_id' => 1,
                'priority'          => 1,
                'price'             => 10.12,
                'date_start'        => null,
                'date_end'          => null,
            ];
            $productSpecial->validate($data);
        } catch (ValidationException $e) {
            $errors = $productSpecial->errors()['validation'];
        }
        $this->assertEquals(0, count($errors));
    }
}