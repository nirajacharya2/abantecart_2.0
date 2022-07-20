<?php

namespace Tests\unit\models\locale;

use Illuminate\Validation\ValidationException;
use abc\models\locale\LengthClass;
use Tests\unit\ATestCase;

/**
 * Class LengthClassModelTest
 */
class LengthClassModelTest extends ATestCase
{


    public function testValidator()
    {

        $language = new LengthClass(
            [
                'length_class_id' => 'gfgfg'
            ]
        );
        $errors = [];
        try {
            $language->validate();
        } catch (ValidationException $e) {
            $errors = $language->errors()['validation'];
        }

        $this->assertCount(1, $errors);

        $language = new LengthClass(
            [

                'length_class_id' => 2,
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