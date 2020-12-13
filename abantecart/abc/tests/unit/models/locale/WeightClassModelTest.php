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
                'weight_class_id' => 0,
            ]
        );
        $errors = [];
        try {
            $weight->validate();
        } catch (ValidationException $e) {
            $errors = $weight->errors()['validation'];
        }
        $this->assertEquals(1, count($errors));

        $weight = new WeightClass(
            [
                'weight_class_id' => 2,
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