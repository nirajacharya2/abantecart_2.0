<?php

namespace abc\tests\unit\models\catalog;

use abc\models\catalog\Category;
use abc\tests\unit\ATestCase;
use Illuminate\Validation\ValidationException;

/**
 * Class CategoryModelTest
 */
class CategoryModelTest extends ATestCase
{

    protected function setUp()
    {

    }

    public function testValidator()
    {
        //validate new Category
        $category = new Category(
            [
                'category_id' => -0.1,
                'uuid'       => -0.00000000021,
                'parent_id'  => -0.1,
                'sort_order' => 'fail',
                'status'     => '55',
            ]
        );
        $errors = [];
        try {
            $category->validate();
        } catch (ValidationException $e) {
            $errors = $category->errors()['validation'];
        }

        $this->assertEquals(5, count($errors));

        //validate new Category
        $existCategory = Category::select(['category_id'])->limit(1)->get()->first();

        $category = new Category(
            [
                'uuid'       => 'string',
                'parent_id'  => $existCategory ? $existCategory->category_id : 1,
                'sort_order' => 100,
                'status'     => 1,
            ]
        );
        $errors = [];
        try {
            $category->validate();
        } catch (ValidationException $e) {
            $errors = $category->errors()['validation'];
            var_dump($errors);
        }
        $this->assertEquals(0, count($errors));

    }

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

    /**
     * @return int
     */
    public function testCreateCategory()
    {
        $arCategory = [
            'sort_order' => 150,
            'status'     => 1,
            'category_description' => [
                1 => [
                    'name'             => 'Test create',
                    'description'      => 'Test description category'
                ]
            ],
        ];
        $categoryId = null;
        try {
            $categoryId = Category::addCategory($arCategory);
        } catch (\PDOException $e) {
            $this->fail($e->getMessage());
        } catch (Warning $e) {
            $this->fail($e->getMessage());
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }

        $this->assertIsInt($categoryId);
        return $categoryId;
    }

    /**
     * @depends testCreateCategory
     *
     * @param int $categoryId
     */
    public function testReadCategory(int $categoryId)
    {
        $category = Category::find($categoryId);
        $this->assertEquals(1, $category->status);
    }

    /**
     * @depends testCreateCategory
     *
     * @param int $categoryId
     */
    public function testUpdateCategory(int $categoryId)
    {
        $arCategory = [
            'sort_order' => 100,
            'status'     => 0,
        ];

        try {
            Category::editCategory($categoryId, $arCategory);
        } catch (\PDOException $e) {
            $this->fail($e->getMessage());
        } catch (Warning $e) {
            $this->fail($e->getMessage());
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }


        $category = Category::find($categoryId);
        $this->assertEquals(100, $category->stort_order);

    }

    /**
     * @depends testCreateCategory
     *
     * @param int $categoryId
     */
    public function testDeleteCategory(int $categoryId)
    {
        try {
            $result = Category::deleteCategory($categoryId);
        } catch (\PDOException $e) {
            $this->fail($e->getMessage());
            $result = false;
        } catch (Warning $e) {
            $this->fail($e->getMessage());
            $result = false;
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
            $result = false;
        }

        $this->assertEquals(true, $result);
    }


}
