<?php

namespace Tests\unit\models\catalog;

use abc\models\catalog\CategoryDescription;
use Illuminate\Validation\ValidationException;
use Tests\unit\ATestCase;

/**
 * Class CategoryDescriptionModelTest
 *
 * @package abc\tests\unit\models\catalog
 */
class CategoryDescriptionModelTest extends ATestCase
{

    public function testValidator()
    {
        $categoryDescription = new CategoryDescription(
            [
                'name'             => '',
                'description'      => 'Test description category'
            ]
        );
        $errors = [];
        try {
            $categoryDescription->validate();
        } catch (ValidationException $e) {
            $errors = $categoryDescription->errors()['validation'];
            //var_dump($errors);
        }

        $this->assertCount(1, $errors);


        $categoryDescription = new CategoryDescription(
            [
                'name'             => 'Correct name',
                'description'      => 'Test description category'
            ]
        );
        $errors = [];
        try {
            $categoryDescription->validate();
        } catch (ValidationException $e) {
            $errors = $categoryDescription->errors()['validation'];
            //var_dump($errors);
        }
        $this->assertCount(0, $errors);

    }


}
