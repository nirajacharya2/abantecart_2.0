<?php

namespace Tests\unit\models\locale;

use Illuminate\Validation\ValidationException;
use abc\models\locale\Location;
use Tests\unit\ATestCase;

/**
 * Class LocationModelTest
 */
class LocationModelTest extends ATestCase
{

    public function testValidator()
    {

        $location = new Location(
            [
                'location_id' => 0,
                'name' => 'somestringsomestringsomestringsomestringsomestringsomestringsomestringsomestringsomestring',
                'description' => ''
            ]
        );
        $errors = [];
        try {
            $location->validate();
        } catch (ValidationException $e) {
            $errors = $location->errors()['validation'];
        }
        $this->assertCount(2, $errors);

        $location = new Location(
            [
                'location_id' => 2,
                'name' => 'USA',
                'description' => 'All States'
            ]
        );
        $errors = [];
        try {
            $location->validate();
        } catch (ValidationException $e) {
            $errors = $location->errors()['validation'];
        }
        $this->assertCount(0, $errors);

    }

}