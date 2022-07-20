<?php

namespace Tests\unit\models\catalog;

use abc\models\catalog\ProductDiscount;
use Illuminate\Validation\ValidationException;
use Tests\unit\ATestCase;

/**
 * Class ProductDiscountModelTest
 */
class ProductDiscountModelTest extends ATestCase
{

    public function testValidator()
    {
        $productDiscount = new ProductDiscount();
        $errors = [];
        try {
            $data = [
                'product_id'        => false,
                'customer_group_id' => false,
                'priority'          => false,
                'quantity'          => false,
                'price'             => false,
                'date_start'        => '0000-00-00-000',
                'date_end'          => '0000-00-00-000',
            ];
            $productDiscount->validate($data);
        } catch (ValidationException $e) {
            $errors = $productDiscount->errors()['validation'];
        }

        $this->assertCount(7, $errors);

        $errors = [];
        try {
            $data = [
                'product_id'        => 50,
                'customer_group_id' => 1,
                'priority'          => 1,
                'quantity'          => 10,
                'price'             => 10.12,
                'date_start'        => date('Y-m-d H:i:s'),
                'date_end'          => date('Y-m-d H:i:s'),
            ];
            $productDiscount->validate($data);
        } catch (ValidationException $e) {
            $errors = $productDiscount->errors()['validation'];
        }
        $this->assertCount(0, $errors);

        $errors = [];
        try {
            $data = [
                'product_id'        => 50,
                'customer_group_id' => 1,
                'priority'          => 1,
                'quantity'          => 10,
                'price'             => 10.12,
                'date_start'        => null,
                'date_end'          => null,
            ];
            $productDiscount->validate($data);
        } catch (ValidationException $e) {
            $errors = $productDiscount->errors()['validation'];
        }
        $this->assertCount(0, $errors);
    }
}