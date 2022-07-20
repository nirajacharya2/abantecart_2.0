<?php

namespace Tests\unit\models\catalog;

use abc\models\catalog\ProductOptionDescription;
use Illuminate\Validation\ValidationException;
use Tests\unit\ATestCase;

/**
 * Class ProductOptionDescriptionModelTest
 */
class ProductOptionDescriptionModelTest extends ATestCase
{

    public function testValidator()
    {
        $productOptionDescription = new ProductOptionDescription();
        $errors = [];
        try {
            $data = [
                'language_id'        => false,
                'product_id'         => false,
                'product_option_id'  => false,
                'name'               => false,
                'option_placeholder' => false,
                'error_text'         => false,
            ];
            $productOptionDescription->validate($data);
        } catch (ValidationException $e) {
            $errors = $productOptionDescription->errors()['validation'];
        }

        $this->assertCount(6, $errors);

        $errors = [];
        try {
            $data = [
                'language_id'        => 1,
                'product_id'         => 50,
                'product_option_id'  => 307,
                'name'               => 'unit test option',
                'option_placeholder' => 'some placeholder text',
                'error_text'         => 'Oops..you did something wrong.',
            ];
            $productOptionDescription->validate($data);
        } catch (ValidationException $e) {
            $errors = $productOptionDescription->errors()['validation'];
        }
        $this->assertCount(0, $errors);

        $errors = [];
        try {
            $data = [
                'language_id'        => 555599999,
                'product_id'         => 1555888,
                'product_option_id'  => 0,
                'name'               => 'unit test option',
                'option_placeholder' => 'some placeholder text',
                'error_text'         => 'Oops..you did something wrong.',
            ];
            $productOptionDescription->validate($data);
        } catch (ValidationException $e) {
            $errors = $productOptionDescription->errors()['validation'];
        }
        $this->assertCount(3, $errors);

    }
}