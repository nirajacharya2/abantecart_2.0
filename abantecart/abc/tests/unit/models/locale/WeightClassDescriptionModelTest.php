<?php

namespace Tests\unit\models\locale;

use abc\models\locale\WeightClassDescription;
use Illuminate\Validation\ValidationException;
use Tests\unit\ATestCase;

/**
 * Class WeightClassDescriptionModelTest
 */
class WeightClassDescriptionModelTest extends ATestCase
{

    public function testValidator()
    {

        $weight = new WeightClassDescription(
            [
                'id' => 0,
                'title' => 4567890876543,
                'unit' => 'ffffffff',

            ]
        );
        $errors = [];
        try {
            $weight->validate();
        } catch (ValidationException $e) {
            $errors = $weight->errors()['validation'];
        }
        $this->assertCount(3, $errors);

        $weight = new WeightClassDescription(
            [

                'title' => 'somestring',
                'unit' => 'str',
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