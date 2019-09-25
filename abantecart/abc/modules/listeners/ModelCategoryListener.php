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
            if($category && $category->category_id && $category->path == ''){
                $category::setCurrentLanguageID(1);
                $path = $category->getPath($category->category_id, 'id');
                Registry::db()->table('categories')
                              ->where('category_id', '=', $category->category_id)
                              ->update(['path' => $path]);
            }
        } catch (\PDOException $e) {
            Registry::log()->write($e->getMessage());
        }

        return [ true ];
    }

}
