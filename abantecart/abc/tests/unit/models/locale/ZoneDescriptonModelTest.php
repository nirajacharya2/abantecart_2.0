<?php

namespace abc\tests\unit;

use abc\models\locale\ZoneDescription;
use Illuminate\Validation\ValidationException;

/**
 * Class ZoneDescriptonModelTest
 */
class ZoneDescriptonModelTest extends ATestCase
{

    public function testValidator()
    {

        $zone = new ZoneDescription(
            [
                'id' => 0,
                'name' => 43647890965546786543,

            ]
        );
        $errors = [];
        try {
            $zone->validate();
        } catch (ValidationException $e) {
            $errors = $zone->errors()['validation'];
        }
        $this->assertEquals(2, count($errors));

        $zone = new ZoneDescription(
            [
                'id' => 2,
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