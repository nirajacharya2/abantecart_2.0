<?php

namespace abc\tests\unit;

use abc\models\catalog\ProductOptionDescription;
use Illuminate\Validation\ValidationException;

/**
 * Class ProductOptionDescriptionModelTest
 */
class ProductOptionDescriptionModelTest extends ATestCase
{

    protected function setUp()
    {
        //init
    }

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

        $this->assertEquals(6, count($errors));

        $errors = [];
        try {
            $data = [
                'language_id'        => 1,
                'product_id'         => 50,
                'product_option_id'  => 307,
                'name'               => 'unit test option',
                'option_placeholder' => 'some placeholder text',
                'error_text'         => 'Oooops..you did something wrong.',
            ];
            $productOptionDescription->validate($data);
        } catch (ValidationException $e) {
            $errors = $productOptionDescription->errors()['validation'];
        }
        $this->assertEquals(0, count($errors));

        $errors = [];
        try {
            $data = [
                'language_id'        => 555599999,
                'product_id'         => 1555888,
                'product_option_id'  => 0,
                'name'               => 'unit test option',
                'option_placeholder' => 'some placeholder text',
                'error_text'         => 'Oooops..you did something wrong.',
            ];
            $productOptionDescription->validate($data);
        } catch (ValidationException $e) {
            $errors = $productOptionDescription->errors()['validation'];
        }
        $this->assertEquals(3, count($errors));

    }
}