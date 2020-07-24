<?php
namespace abc\tests\unit;
use abc\models\locale\ZoneDescription;
use Illuminate\Validation\ValidationException;

/**
 * Class ZoneDescriptonModelTest
 */
class ZoneDescriptonModelTest extends ATestCase{

    public function testValidator()
    {

        $zone = new ZoneDescription(
            [
                'name' => 43647890965546786543,

            ]
        );
        $errors = [];
        try {
            $zone->validate();
        } catch (ValidationException $e) {
            $errors =$zone->errors()['validation'];
        }
        $this->assertEquals(1, count($errors));

        $zone= new ZoneDescription(
            [

                'code' => 'somestrng',
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