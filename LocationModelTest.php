<?php
namespace abc\tests\unit;
use Illuminate\Validation\ValidationException;
use abc\models\locale\Location;
/**
 * Class LocationModelTest
 */
class LocationModelTest extends ATestCase{

    public function testValidator()
    {

        $location = new Location(
            [
                'name'=>'somestringsomestringsomestringsomestringsomestringsomestringsomestringsomestringsomestring',
                'description'=>43434
            ]
        );
        $errors = [];
        try {
            $location->validate();
        } catch (ValidationException $e) {
            $errors =$location->errors()['validation'];
        }
        $this->assertEquals(2, count($errors));

        $location= new Location(
            [
                'name'=>'USA',
                'description'=>'All States'
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