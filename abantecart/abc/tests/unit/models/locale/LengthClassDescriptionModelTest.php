<?php

namespace Tests\unit\models\locale;

use Illuminate\Validation\ValidationException;
use abc\models\locale\LengthClassDescription;
use Tests\unit\ATestCase;

/**
 * Class LengthClassDescriptionModelTest
 */
class LengthClassDescriptionModelTest extends ATestCase
{


    public function testValidator()
    {

        $language = new LengthClassDescription(
            [
                'id'=> 0,
                'title' => '',
                'unit' => 'somestring'
            ]
        );
        $errors = [];
        try {
            $language->validate();
        } catch (ValidationException $e) {
            $errors = $language->errors()['validation'];
        }
        $this->assertCount(3, $errors);

        $language = new LengthClassDescription(
            [
                'id'=> 2,
                'title' => 'somestring',
                'unit' => 'str'
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