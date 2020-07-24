<?php
namespace abc\tests\unit;
use Illuminate\Validation\ValidationException;
use abc\models\locale\LengthClassDescription;
/**
 * Class LengthClassDescriptionModelTest
 */
class LengthClassDescriptionModelTest extends ATestCase{


    public function testValidator()
    {

        $language = new LengthClassDescription(
            [
                'title' => '',
                'unit'=>'somestring'
            ]
        );
        $errors = [];
        try {
            $language->validate();
        } catch (ValidationException $e) {
            $errors =$language->errors()['validation'];
        }
        var_dump($errors);
        $this->assertEquals(2, count($errors));

        $language= new LengthClassDescription(
            [

                'title' => 'somestring',
                'unit'=>'str'
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