<?php

namespace Tests\unit\models\catalog;

use abc\models\catalog\ProductTag;
use Illuminate\Validation\ValidationException;
use Tests\unit\ATestCase;

/**
 * Class ProductTagModelTest
 */
class ProductTagModelTest extends ATestCase
{

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

        $this->assertCount(3, $errors);

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

        $this->assertCount(0, $errors);

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

        $this->assertCount(1, $errors);

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

        $this->assertCount(1, $errors);

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

        $this->assertCount(1, $errors);

    }
}