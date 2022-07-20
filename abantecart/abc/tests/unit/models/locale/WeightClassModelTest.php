<?php

namespace Tests\unit\models\locale;

use Illuminate\Validation\ValidationException;
use abc\models\locale\WeightClass;
use Tests\unit\ATestCase;

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
        $this->assertCount(1, $errors);

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
        $this->assertCount(0, $errors);

    }
}