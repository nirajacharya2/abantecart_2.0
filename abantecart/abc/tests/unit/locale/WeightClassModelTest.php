<?php

namespace abc\tests\unit;

use Illuminate\Validation\ValidationException;
use abc\models\locale\WeightClass;


/**
 * Class WeightClassModelTest
 */
class WeightClassModelTest extends ATestCase
{


    public function testValidator()
    {

        $weight = new WeightClass(
            [
                'value' => 100.8,
            ]
        );
        $errors = [];
        try {
            $weight->validate();
        } catch (ValidationException $e) {
            $errors = $weight->errors()['validation'];
        }

        $this->assertEquals(0, count($errors));

        $weight = new WeightClass(
            [
                'value' => 8.5,
            ]
        );
        $errors = [];
        try {
            $weight->validate();
        } catch (ValidationException $e) {
            $errors = $weight->errors()['validation'];
        }
        $this->assertEquals(0, count($errors));

    }
}