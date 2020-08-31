<?php
namespace abc\tests\unit;
use abc\models\locale\WeightClassDescription;
use Illuminate\Validation\ValidationException;

/**
 * Class WeightClassDescriptionModelTest
 */
class WeightClassDescriptionModelTest extends ATestCase{

    public function testValidator()
    {

        $weight = new WeightClassDescription(
            [
                'title' => 4567890876543,
                'unit' => 'ffffffff',

            ]
        );
        $errors = [];
        try {
            $weight->validate();
        } catch (ValidationException $e) {
            $errors =$weight->errors()['validation'];
        }
        $this->assertEquals(2, count($errors));

        $weight= new WeightClassDescription(
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
        $this->assertEquals(0, count($errors));

    }
}