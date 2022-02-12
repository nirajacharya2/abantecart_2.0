<?php

namespace Tests\unit\models\locale;

use abc\models\locale\ZoneDescription;
use Illuminate\Validation\ValidationException;
use Tests\unit\ATestCase;

/**
 * Class ZoneDescriptionModelTest
 */
class ZoneDescriptionModelTest extends ATestCase
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
        $this->assertCount(2, $errors);

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
        $this->assertCount(0, $errors);

    }

}