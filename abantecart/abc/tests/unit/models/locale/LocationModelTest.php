<?php

namespace abc\tests\unit;

use Illuminate\Validation\ValidationException;
use abc\models\locale\Location;

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
        $this->assertEquals(2, count($errors));

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
        $this->assertEquals(0, count($errors));

    }

}