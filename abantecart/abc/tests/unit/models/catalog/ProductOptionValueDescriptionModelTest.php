<?php

namespace abc\tests\unit;

use abc\models\catalog\ProductOptionValue;
use abc\models\catalog\ProductOptionValueDescription;
use Illuminate\Validation\ValidationException;

/**
 * Class ProductOptionValueDescriptionModelTest
 */
class ProductOptionValueDescriptionModelTest extends ATestCase
{

    protected function setUp()
    {
        //init
    }

    public function testValidator()
    {
        $valueDescription = new ProductOptionValueDescription();
        $errors = [];
        try {
            $data = [
                'language_id'             => false,
                'product_id'              => false,
                'product_option_value_id' => false,
                'name'                    => false,
                'grouped_attribute_names' => false,
            ];
            $valueDescription->validate($data);
        } catch (ValidationException $e) {
            $errors = $valueDescription->errors()['validation'];
        }

        $this->assertEquals(4, count($errors));

        $errors = [];
        try {
            $data = [
                'language_id'             => 1,
                'product_id'              => 50,
                'product_option_value_id' => 612,
                'name'                    => 'unit test option',
                'grouped_attribute_names' => ['test' => 'testttt'],
            ];
            $valueDescription->validate($data);
        } catch (ValidationException $e) {
            $errors = $valueDescription->errors()['validation'];
        }
        $this->assertEquals(0, count($errors));

    }
}