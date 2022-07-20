<?php

namespace Tests\unit\models\locale;

use abc\models\locale\Zone;
use Illuminate\Validation\ValidationException;
use Tests\unit\ATestCase;

/**
 * Class ZoneModelTest
 */
class ZoneModelTest extends ATestCase
{
    public function testValidator()
    {

        $zone = new Zone(
            [
                'zone_id' => 0,
                'code' => 43647890965546786543,
                'status' => 'sdgdgsd',
                'sort_order' => 'sdgsdgsd',
            ]
        );
        $errors = [];
        try {
            $zone->validate();
        } catch (ValidationException $e) {
            $errors = $zone->errors()['validation'];
        }
        $this->assertCount(4, $errors);

        $zone = new Zone(
            [
                'id' => 2,
                'code' => 'somestrng',
                'status' => 1,
                'sort_order' => 2,
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