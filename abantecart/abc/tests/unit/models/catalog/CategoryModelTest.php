<?php
namespace abc\tests\unit\models\catalog;

use abc\models\catalog\Category;
use abc\tests\unit\ATestCase;

/**
 * Class CategoryModelTest
 */
class CategoryModelTest extends ATestCase{


    protected function setUp(){

    }

    /*public function testValidator(){

    }*/

    public function testGetCategoryByName()
    {
        $category = Category::getCategoryByName('skincare');
        $this->assertCount(15, $category);
        $this->assertEquals(null, $category['parent_id']);

        //check with parent_id
        $category = Category::getCategoryByName('skincare', 58);
        $this->assertCount(15, $category);
        $this->assertEquals(58, $category['parent_id']);
    }
}