<?php

namespace abc\tests\unit\models\catalog;

use abc\models\catalog\Category;
use abc\models\catalog\CategoryDescription;
use abc\tests\unit\ATestCase;
use Illuminate\Validation\ValidationException;

/**
 * Class CategoryDescriptionModelTest
 *
 * @package abc\tests\unit\models\catalog
 */
class CategoryDescriptionModelTest extends ATestCase
{

    protected function setUp()
    {

    }

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

        $this->assertEquals(1, count($errors));


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
        $this->assertEquals(0, count($errors));

    }


}
