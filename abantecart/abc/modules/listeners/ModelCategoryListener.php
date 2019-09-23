<?php

namespace abc\modules\listeners;

use abc\core\engine\Registry;
use abc\models\catalog\Category;

class ModelCategoryListener
{

    /**
     * @param $eventAlias
     * @param Category $category
     * @param array $options
     *
     * @return array
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     */
    protected function handle($eventAlias, $category, $options = [])
    {

        if (!is_object($category)
            || !($category instanceof Category)
        ) {
            return [
                false,
                __CLASS__.': Argument 1 is not instance of model '.Category::class
            ];
        }

        try {

            if($category && $category->parent_id && $category->path == ''){
                $category->path = $category->getPath($category->category_id,'id');
                $category->save();
            }
        } catch (\PDOException $e) {
            Registry::log()->write($e->getMessage());
        }

        return [ true ];
    }

}
