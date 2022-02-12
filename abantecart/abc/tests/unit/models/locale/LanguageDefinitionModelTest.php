<?php

namespace Tests\unit\models\locale;

use abc\models\locale\LanguageDefinition;
use Illuminate\Validation\ValidationException;
use Tests\unit\ATestCase;

/**
 * Class LanguageDefinitionModelTest
 */
class LanguageDefinitionModelTest extends ATestCase
{


    public function testValidator()
    {

        $language = new LanguageDefinition(
            [
                'language_definition_id' => 0,
                'language_value' => 1,
            ]
        );
        $errors = [];
        try {
            $language->validate();
        } catch (ValidationException $e) {
            $errors = $language->errors()['validation'];
        }

        $this->assertCount(2, $errors);

        $language = new LanguageDefinition(
            [
                'language_definition_id' => 2,
                'language_value' => 'somestring',

            ]
        );
        $errors = [];
        try {
            $language->validate();
        } catch (ValidationException $e) {
            $errors = $language->errors()['validation'];
        }
        $this->assertCount(0, $errors);

    }
}