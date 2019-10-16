<?php

namespace abc\tests\unit;

use abc\models\catalog\ProductTag;
use Illuminate\Validation\ValidationException;

/**
 * Class ProductTagModelTest
 */
class ProductTagModelTest extends ATestCase
{

    protected function setUp()
    {
        //init
    }

    public function testValidator()
    {
        $productsRelated = new ProductTag();
        $errors = [];
        try {
            $data = [
                'product_id'  => false,
                'language_id' => false,
                'tag'         => null,
            ];
            $productsRelated->validate($data);
        } catch (ValidationException $e) {
            $errors = $productsRelated->errors()['validation'];
        }

        $this->assertEquals(3, count($errors));

        $errors = [];
        try {
            $data = [
                'product_id'  => 50,
                'language_id' => 1,
                'tag'         => 'sometesttag',
            ];
            $productsRelated->validate($data);
        } catch (ValidationException $e) {
            $errors = $productsRelated->errors()['validation'];
        }

        $this->assertEquals(0, count($errors));

        $errors = [];
        try {
            $data = [
                'product_id'  => 5000000,
                'language_id' => 1,
                'tag'         => 'sometesttag',
            ];
            $productsRelated->validate($data);
        } catch (ValidationException $e) {
            $errors = $productsRelated->errors()['validation'];
        }

        $this->assertEquals(1, count($errors));

        $errors = [];
        try {
            $data = [
                'product_id'  => 50,
                'language_id' => 522222,
                'tag'         => 'sometesttag',
            ];
            $productsRelated->validate($data);
        } catch (ValidationException $e) {
            $errors = $productsRelated->errors()['validation'];
        }

        $this->assertEquals(1, count($errors));

        $errors = [];
        try {
            $data = [
                'product_id'  => 50,
                'language_id' => 1,
                'tag'         => '',
            ];
            $productsRelated->validate($data);
        } catch (ValidationException $e) {
            $errors = $productsRelated->errors()['validation'];
        }

        $this->assertEquals(1, count($errors));

    }
}