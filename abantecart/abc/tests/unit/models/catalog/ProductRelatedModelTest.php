<?php

namespace abc\tests\unit;

use abc\models\catalog\ProductsRelated;
use Illuminate\Validation\ValidationException;

/**
 * Class ProductRelatedModelTest
 */
class ProductRelatedModelTest extends ATestCase
{

    protected function setUp()
    {
        //init
    }

    public function testValidator()
    {
        $productsRelated = new ProductsRelated();
        $errors = [];
        try {
            $data = [
                'product_id' => false,
                'related_id' => false,
            ];
            $productsRelated->validate($data);
        } catch (ValidationException $e) {
            $errors = $productsRelated->errors()['validation'];
        }

        $this->assertEquals(2, count($errors));

        $errors = [];
        try {
            $data = [
                'product_id' => 50,
                'related_id' => 51,
            ];
            $productsRelated->validate($data);
        } catch (ValidationException $e) {
            $errors = $productsRelated->errors()['validation'];
        }

        $this->assertEquals(0, count($errors));

        $errors = [];
        try {
            $data = [
                'product_id' => 50,
                'related_id' => null,
            ];
            $productsRelated->validate($data);
        } catch (ValidationException $e) {
            $errors = $productsRelated->errors()['validation'];
        }

        $this->assertEquals(1, count($errors));

        $errors = [];
        try {
            $data = [
                'product_id' => 500050,
                'related_id' => 51,
            ];
            $productsRelated->validate($data);
        } catch (ValidationException $e) {
            $errors = $productsRelated->errors()['validation'];
        }

        $this->assertEquals(1, count($errors));

        $errors = [];
        try {
            $data = [
                'product_id' => 50,
                'related_id' => 51000000,
            ];
            $productsRelated->validate($data);
        } catch (ValidationException $e) {
            $errors = $productsRelated->errors()['validation'];
        }

        $this->assertEquals(1, count($errors));
    }
}