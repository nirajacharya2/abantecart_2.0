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

class CategoryModelTest extends ATestCase
{

    public function testGetPath()
    {
        /** @var Category $category */
        $category = Category::where('parent_id', '>', 0)->first();

        $path = $category->getPath($category->category_id,'id');
        $this->assertIsInt(strpos($path, '_'));

        $category = Category::whereNull('parent_id')->first();
        $path = $category->getPath($category->category_id,'id');
        $this->assertEquals($path, $category->category_id);
    }
//    public function testCalculatePath()
//    {
//        /** @var Category $category */
//        $category = Category::find(3515);
//
//        $result = $category->getCategoryBranchInfo($category->category_id);
//
//var_Dump($result);
////        $this->assertEquals($path, $category->category_id);
//    }


}