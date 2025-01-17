<?php

namespace Tests\unit\models\locale;

use abc\models\locale\ZonesToLocation;
use Illuminate\Validation\ValidationException;
use Tests\unit\ATestCase;

/**
 * Class ZoneToLocationModelTest
 */
class ZoneToLocationModelTest extends ATestCase
{
    public function testValidator()
    {

        $zone = new ZonesToLocation(
            [
                'zone_to_location_id'=> 'dsfdfdsfd',
                'country_id' => 'fgfg',
                'zone_id' => 'sdgdgsd',
                'location_id' => 'sdgsdgsd',
            ]
        );
        $errors = [];
        try {
            $zone->validate();
        } catch (ValidationException $e) {
            $errors = $zone->errors()['validation'];
        }
        $this->assertEquals(4, count($errors));

        $zone = new ZonesToLocation(
            [
                'zone_to_location_id'=>2,
                'country_id' => 3,
                'zone_id' => 1,
                'location_id' => 2,
            ]
        );
        $errors = [];
        try {
            $zone->validate();
        } catch (ValidationException $e) {
            $errors = $zone->errors()['validation'];
        }
        $this->assertEquals(0, count($errors));

    }


}