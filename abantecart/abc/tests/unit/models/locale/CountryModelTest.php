<?php

namespace Tests\unit\models\locale;

use abc\models\locale\Country;
use Illuminate\Validation\ValidationException;
use Tests\unit\ATestCase;

/**
 * Class CountryModelTest
 */
class CountryModelTest extends ATestCase
{

    public function testValidator()
    {

        $country = new Country(
            [
                'country_id' => 0,
                'iso_code_2' => 121111111,
                'iso_code_3' => 11111111,
                'address_format' => 111111111,
                'status' => 'fgfgf',
                'sort_order' => 'fgnbfgnfgn',
            ]
        );
        $errors = [];
        try {
            $country->validate();
        } catch (ValidationException $e) {
            $errors = $country->errors()['validation'];
        }

        $this->assertCount(6, $errors);

        $country = new Country(
            [
                'iso_code_2' => 'fd',
                'iso_code_3' => 'fdd',
                'address_format' => 'somestring',
                'status' => 1,
                'sort_order' => 2,
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