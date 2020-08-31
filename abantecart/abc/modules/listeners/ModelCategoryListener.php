<?php

namespace abc\modules\listeners;

use abc\core\engine\Registry;
use abc\models\catalog\Category;

class ModelCategoryListener
{

    /**
     * @return array
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function handle()
    {   /** @var Category $category */

        $category = func_get_arg(0);

        if (!is_object($category)
            || !($category instanceof Category)
        ) {
            return [
                false,
                __CLASS__.': Argument 1 is not instance of model '.Category::class
            ];
        }

        try {
            if($category && $category->category_id){
                //calculate and modify current category
                $this->modify($category);
                //also modify parent tree branch
                $oldParentId = (int)$category->getOriginal('parent_id');
                if($oldParentId){
                    $parent = Category::find($oldParentId);
                    if($parent){
                        $this->modify($parent);
                    }
                }
            }
        } catch (\PDOException $e) {
            Registry::log()->write($e->getMessage());
        }

        return [ true ];
    }

    /**
     * @param Category $category
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     */
    protected function modify($category){

        $category::setCurrentLanguageID(1);
        $calc = $category::getCategoryBranchInfo($category->category_id);
        $path = $calc['path'];

        Registry::db()->table('categories')
                      ->where('category_id', '=', $category->category_id)
                      ->update(
                          [
                              'path' => $path,
                              'total_products_count' => $calc['total_products_count'],
                              'active_products_count' => $calc['active_products_count'],
                              'children_count' => count((array)$calc['children'])
                          ]
                      );
        $allParents = explode('_',$path);
        array_pop($allParents);
        $allParents = array_reverse($allParents);
        //tree IDs without current category_id
        foreach($allParents as $parentId){
            $calc = $category::getCategoryBranchInfo((int)$parentId);
            if($calc){
                Registry::db()
                    ->table('categories')
                    ->where('category_id', '=', $parentId)
                    ->update(
                      [
                          'path' => $calc['path'],
                          'total_products_count' => $calc['total_products_count'],
                          'active_products_count' => $calc['active_products_count'],
                          'children_count' => count((array)$calc['children'])
                      ]
                    );
            }
        }
    }
}
