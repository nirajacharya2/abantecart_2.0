<?php
namespace abc\tests\unit;
use abc\models\locale\ZonesToLocation;
use Illuminate\Validation\ValidationException;
/**
 * Class ZoneTolocationModelTest
 */
class ZoneTolocationModelTest extends ATestCase{
    public function testValidator()
    {

        $zone = new ZonesToLocation(
            [
                'country_id' => 'fgfg',
                'zone_id' => 'sdgdgsd',
                'location_id'=> 'sdgsdgsd',
            ]
        );
        $errors = [];
        try {
            $zone->validate();
        } catch (ValidationException $e) {
            $errors =$zone->errors()['validation'];
        }
        $this->assertEquals(3, count($errors));

        $zone= new ZonesToLocation(
            [

                'country_id' => 3,
                'zone_id' => 1,
                'location_id'=> 2,
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