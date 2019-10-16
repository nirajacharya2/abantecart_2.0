<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2018 Belavier Commerce LLC
 *
 * This source file is subject to Open Software License (OSL 3.0)
 * License details is bundled with this package in the file LICENSE.txt.
 * It is also available at this URL:
 * <http://www.opensource.org/licenses/OSL-3.0>
 *
 * UPGRADE NOTE:
 * Do not edit or add to this file if you wish to upgrade AbanteCart to newer
 * versions in the future. If you wish to customize AbanteCart for your
 * needs please refer to http://www.abantecart.com for more information.
 */

namespace abc\tests\unit\models\catalog;

use abc\models\catalog\Category;
use abc\tests\unit\ATestCase;
use Illuminate\Validation\ValidationException;

class CategoryModelTest extends ATestCase
{

    public function testValidator()
    {
        $category = new Category();
        $errors = [];
        try {
            $data = [
                'category_id'           => false,
                'uuid'                  => false,
                'parent_id'             => false,
                'path'                  => false,
                'total_products_count'  => false,
                'active_products_count' => false,
                'children_count'        => false,
                'sort_order'            => false,
                'status'                => 0.000001111,
            ];
            $category->validate($data);
        } catch (ValidationException $e) {
            $errors = $category->errors()['validation'];
        }
        $this->assertEquals(9, count($errors));

        $errors = [];
        try {
            $data = [
                'category_id'           => 1,
                'uuid'                  => 'uuiidddd',
                'parent_id'             => 36,
                'path'                  => '36_22',
                'total_products_count'  => 1,
                'active_products_count' => 1,
                'children_count'        => 0,
                'sort_order'            => 1,
                'status'                => true,
            ];
            $category->validate($data);
        } catch (ValidationException $e) {
            $errors = $category->errors()['validation'];
            var_Dump($errors);
        }
        $this->assertEquals(0, count($errors));
    }

    public function testGetPath()
    {
        /** @var Category $category */
        $category = Category::where('parent_id', '>', 0)->first();

        $path = $category->getPath($category->category_id, 'id');
        $this->assertIsInt(strpos($path, '_'));

        $category = Category::whereNull('parent_id')->first();
        $path = $category->getPath($category->category_id, 'id');
        $this->assertEquals($path, $category->category_id);
    }
//    public function testGetChildrenIDs()
//    {
//        /** @var Category $category */
//        $children = Category::getChildrenIDs(90);
//
//
//var_Dump($children);
////        $this->assertEquals($path, $category->category_id);
//    }

}