<?php
namespace abc\tests\unit;
use abc\models\locale\LanguageDefinition;
use Illuminate\Validation\ValidationException;
/**
 * Class LenguageDefinitionModelTest
 */
class LenguageDefinitionModelTest extends ATestCase{


    public function testValidator()
    {

        $language = new LanguageDefinition(
            [
                'language_value' => '',
            ]
        );
        $errors = [];
        try {
            $language->validate();
        } catch (ValidationException $e) {
            $errors =$language->errors()['validation'];
        }
        $this->assertEquals(1, count($errors));

        $language= new LanguageDefinition(
            [

                'language_value' => 'somestring',

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