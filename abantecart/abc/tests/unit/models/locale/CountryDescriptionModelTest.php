<?php

namespace Tests\unit\models\locale;

use abc\models\locale\CountryDescription;
use Illuminate\Validation\ValidationException;
use Tests\unit\ATestCase;

/**
 * Class CountryDescriptionModelTest
 */
class CountryDescriptionModelTest extends ATestCase
{


    public function testValidator()
    {

        $country = new CountryDescription(
            [
                'id' => -1,
                'name' => '',
                'language_id' => 'fvf',
            ]
        );
        $errors = [];
        try {
            $country->validate();
        } catch (ValidationException $e) {
            $errors = $country->errors()['validation'];
        }

        $this->assertCount(3, $errors);


        $country = new CountryDescription(
            [
                'id' => 2,
                'name' => 'somestring',
                'language_id' => 1,
            ]
        );
        $errors = [];
        try {
            $country->validate();
        } catch (ValidationException $e) {
            $errors = $country->errors()['validation'];
        }

        $this->assertCount(0, $errors);

    }
}