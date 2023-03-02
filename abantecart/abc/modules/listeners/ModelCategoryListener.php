<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2023 Belavier Commerce LLC
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
 *
 */
namespace abc\modules\listeners;

use abc\core\engine\Registry;
use abc\core\lib\AException;
use abc\models\catalog\Category;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;

class ModelCategoryListener
{

    /**
     * @return array
     * @throws InvalidArgumentException
     */
    public function handle()
    {
        /** @var Category $category */

        $category = func_get_arg(0);

        if (!is_object($category)
            || !($category instanceof Category)
        ) {
            return [
                false,
                __CLASS__ . ': Argument 1 is not instance of model ' . Category::class
            ];
        }

        try {
            if ($category->category_id) {
                //calculate and modify current category
                $this->modify($category);
                //also modify parent tree branch
                $oldParentId = (int)$category->getOriginal('parent_id');
                if ($oldParentId) {
                    $parent = Category::find($oldParentId);
                    if ($parent) {
                        $this->modify($parent);
                    }
                }
            }
        } catch (\Exception|\Error $e) {
            Registry::log()->critical($e->getMessage());
        }

        return [true];
    }

    /**
     * @param Category $category
     *
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws AException
     */
    protected function modify($category)
    {
        $db = Registry::db();
        $category::setCurrentLanguageID(1);
        $calc = $category::getCategoryBranchInfo($category->category_id);
        $path = $calc['path'];

        $db->table('categories')
            ->where('category_id', '=', $category->category_id)
            ->update(
                [
                    'path'                  => $path,
                    'total_products_count'  => $calc['total_products_count'],
                    'active_products_count' => $calc['active_products_count'],
                    'children_count'        => count((array)$calc['children'])
                ]
            );
        $allParents = explode('_', $path);
        array_pop($allParents);
        $allParents = array_reverse($allParents);
        //tree IDs without current category_id
        foreach ($allParents as $parentId) {
            $calc = $category::getCategoryBranchInfo((int)$parentId);
            if ($calc) {
                $db->table('categories')
                    ->where('category_id', '=', $parentId)
                    ->update(
                        [
                            'path'                  => $calc['path'],
                            'total_products_count'  => $calc['total_products_count'],
                            'active_products_count' => $calc['active_products_count'],
                            'children_count'        => count((array)$calc['children'])
                        ]
                    );
            }
        }
    }
}