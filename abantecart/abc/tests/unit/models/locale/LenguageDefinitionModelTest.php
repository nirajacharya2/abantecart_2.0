<?php

namespace abc\tests\unit;

use abc\models\locale\LanguageDefinition;
use Illuminate\Validation\ValidationException;

/**
 * Class LenguageDefinitionModelTest
 */
class LenguageDefinitionModelTest extends ATestCase
{


    public function testValidator()
    {

        $language = new LanguageDefinition(
            [
                'language_definition_id'=>'dfsgdg',
                'language_value' => '',
            ]
        );
        $errors = [];
        try {
            $language->validate();
        } catch (ValidationException $e) {
            $errors = $language->errors()['validation'];
        }

        $this->assertEquals(2, count($errors));

        $language = new LanguageDefinition(
            [
                'language_definition_id'=>2,
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