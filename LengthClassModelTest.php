<?php
namespace abc\tests\unit;
use Illuminate\Validation\ValidationException;
use abc\models\locale\LengthClass;
/**
 * Class LengthClassModelTest
 */
class LengthClassModelTest extends ATestCase{


    public function testValidator()
    {

        $language = new LengthClass(
            [
                'value' => 16,
            ]
        );
        $errors = [];
        try {
            $language->validate();
        } catch (ValidationException $e) {
            $errors =$language->errors()['validation'];
        }
        var_dump($errors);
        $this->assertEquals(1, count($errors));

        $language= new LengthClass(
            [

                'value' => 15.7,
            ]
        );
        $errors = [];
        try {
            $language->validate();
        } catch (ValidationException $e) {
            $errors = $language->errors()['validation'];
        }
        $this->assertEquals(0, count($errors));

    }
}