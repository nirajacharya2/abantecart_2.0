<?php

namespace abc\tests\unit;

use Illuminate\Validation\ValidationException;
use abc\models\locale\LengthClassDescription;

/**
 * Class LengthClassDescriptionModelTest
 */
class LengthClassDescriptionModelTest extends ATestCase
{


    public function testValidator()
    {

        $language = new LengthClassDescription(
            [
                'id'=> 'dfdfd',
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
        var_dump($errors);
        $this->assertEquals(3, count($errors));

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
        $this->assertEquals(0, count($errors));

    }
}