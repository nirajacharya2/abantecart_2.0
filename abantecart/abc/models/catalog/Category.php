<?php

namespace abc\models\catalog;

use abc\core\ABC;
use abc\core\engine\AResource;
use abc\core\engine\Registry;
use abc\core\lib\AException;
use abc\core\lib\ALayoutManager;
use abc\core\lib\AResourceManager;
use abc\models\BaseModel;
use abc\models\QueryBuilder;
use abc\models\system\Setting;
use abc\models\system\Store;
use Cake\Database\Exception;
use Dyrynda\Database\Support\GeneratesUuid;
use H;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use phpDocumentor\Reflection\Types\Integer;

/**
 * Class Category
 *
 * @property int                                      $category_id
 * @property int                                      $parent_id
 * @property int                                      $sort_order
 * @property int                                      $status
 * @property \Carbon\Carbon                           $date_added
 * @property \Carbon\Carbon                           $date_modified
 *
 * @property \Illuminate\Database\Eloquent\Collection $categories_to_stores
 * @property \Illuminate\Database\Eloquent\Collection $category_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $products_to_categories
 *
 * @package abc\models
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Category extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes, GeneratesUuid;

    protected $cascadeDeletes = [
        'descriptions',
        'products',
        //'stores',
    ];
    /**
     * @var string
     */
    protected $primaryKey = 'category_id';
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $casts = [
        'parent_id'  => 'int',
        'sort_order' => 'int',
        'status'     => 'int',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'date_added',
        'date_modified',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'category_id',
        'parent_id',
        'sort_order',
        'status',
        'uuid',
        'date_deleted',
    ];

    protected $rules = [
        /** @see validate() */
        'category_id' => [
            'checks'   => [
                'integer',
            ],
            'messages' => [
                '*' => ['default_text' => 'Category ID is not Integer!'],
            ],
        ],
        'uuid'        => [
            'checks'   => [
                'string',
                'max:64',
                'nullable',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be a string less than 255 characters!',
                ],
            ],
        ],
        'parent_id'   => [
            'checks'   => [
                'integer',
                'nullable',
                'exists:categories',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'Category ID must be an integer and present in database!',
                ],
            ],
        ],
        'sort_order'  => [
            'checks'   => [
                'integer',
            ],
            'messages' => [
                '*' => ['default_text' => 'Sort Order is not Integer!'],
            ],
        ],
        'status'      => [
            'checks'   => [
                'integer',
                'digits:1',
            ],
            'messages' => [
                '*' => ['default_text' => 'Status must be 1 or 0 !'],
            ],
        ],
    ];

    /**
     * @return mixed
     */
    public function descriptions()
    {
        return $this->hasMany(CategoryDescription::class, 'category_id');
    }

    /**
     * @return mixed
     */
    public function description()
    {
        return $this->hasOne(CategoryDescription::class, 'category_id')
            ->where('language_id', '=', $this->registry->get('language')->getContentLanguageID());
    }

    /**
     * @return mixed
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'products_to_categories', 'product_id', 'category_id');
    }

    /**
     * @return mixed
     */
    public function stores()
    {
        return $this->belongsToMany(Store::class, 'categories_to_stores', 'category_id', 'store_id');
    }

    /**
     * @param        $category_id
     * @param string $mode
     *
     * @return string
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     */
    public static function getPath($category_id, $mode = '')
    {
        $registry = Registry::getInstance();
        $db = $registry->get('db');

        $category_id = (int)$category_id;

        $categories = $db->table('categories as c')
            ->leftJoin('category_descriptions as cd', 'c.category_id', '=', 'cd.category_id')
            ->where('c.category_id', '=', (int)$category_id)
            ->where('cd.language_id', '=', $registry->get('language')->getContentLanguageID())
            ->orderBy('c.sort_order')
            ->orderBy('cd.name')
            ->get()
            ->toArray();

        $category_info = current($categories);

        if ($category_info->parent_id) {
            return $mode === 'id'
                ? self::getPath($category_info->parent_id, $mode).'_'
                .$category_info->category_id
                : self::getPath($category_info->parent_id, $mode)
                .$registry->get('language')->get('text_separator')
                .$category_info->name;
        }

        return $mode === 'id' ? $category_info->category_id : $category_info->name;
    }

    /**
     * @param      $parentId
     * @param null $storeId
     * @param int  $limit
     *
     * @return array|false|mixed
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     */
    public static function getCategories($parentId, $storeId = null, $limit = 0)
    {
        $registry = Registry::getInstance();
        $config = $registry->get('config');
        $cacheInst = $registry->get('cache');

        if (ABC::env('IS_ADMIN')) {
            $languageId = (int)$registry->get('language')->getContentLanguageID();
        } else {
            $languageId = (int)$config->get('storefront_language_id');
        }

        $cacheKey = 'category.list.'.$parentId.'.store_'.$storeId.'_limit_'.$limit.'_lang_'.$languageId;
        $cache = $cacheInst->pull($cacheKey);

        if ($cache === false) {
            $category_data = [];

            /** @var QueryBuilder $categories */
            $categories = self::leftJoin('category_descriptions', 'categories.category_id', '=', 'category_descriptions.category_id');
            if ($storeId !== null) {
                $categories = $categories->rightJoin('categories_to_stores', function ($join) use ($storeId) {
                    $join->on('categories.category_id', '=', 'categories_to_stores.category_id')
                        ->where('categories_to_stores.store_id', '=', (int)$storeId);
                });
            }

            if ((int)$parentId > 0) {
                $categories = $categories->where('categories.parent_id', '=', (int)$parentId);
            } else {
                $categories = $categories->whereNull('categories.parent_id');
            }

            $categories = $categories->where('category_descriptions.language_id', '=', $languageId)
                ->active('categories')
                ->orderBy('categories.sort_order')
                ->orderBy('category_descriptions.name')
                ->get();

//            \H::df($this->db->getQueryLog());

            foreach ($categories as $category) {
                $name = $category->name;
                if (ABC::env('IS_ADMIN')) {
                    $name = self::getPath($category->category_id);
                }
                $category_data[] = [
                    'category_id' => $category->category_id,
                    'parent_id'   => $category->parent_id,
                    'name'        => $name,
                    'status'      => $category->status,
                    'sort_order'  => $category->sort_order,
                ];
                $category_data = array_merge($category_data, self::getCategories($category->category_id, $storeId));
            }
            $cache = $category_data;
            $cacheInst->push($cacheKey, $cache);
        }

        return $cache;
    }

    /**
     * @param $categoryId
     *
     * @return array|bool|false|mixed
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     */
    public static function getCategory($categoryId)
    {
        if (!$categoryId) {
            return false;
        }

        $registry = Registry::getInstance();
        $db = $registry->get('db');
        $cacheInst = $registry->get('cache');

        $storeId = (int)$registry->get('config')->get('config_store_id');
        if (ABC::env('IS_ADMIN')) {
            $languageId = (int)$registry->get('language')->getContentLanguageID();
        } else {
            $languageId = (int)$registry->get('storefront_language_id');
        }

        $cacheKey = 'product.listing.category.'.(int)$categoryId.'.store_'.$storeId.'_lang_'.$languageId;
        $cache = $cacheInst->pull($cacheKey);
        if ($cache === false) {

            $arSelect = ['*'];

            if (ABC::env('IS_ADMIN')) {
                $arSelect[] = $db->raw('(SELECT keyword FROM '.$db->table_name('url_aliases')
                    ." WHERE query = 'category_id=".$categoryId."' AND language_id='".$languageId."' ) as keyword");
            } else {
                $arSelect[] = $db->raw('(SELECT COUNT(p2c.product_id) as cnt
										 FROM '.$db->table_name('products_to_categories').' p2c
										 INNER JOIN '.$db->table_name('products')." p ON p.product_id = p2c.product_id AND p.status = '1'
										 WHERE  p2c.category_id = ".$db->table_name('categories').'.category_id) as products_count');
            }

            $category = self::select($arSelect);

            $category = $category->leftJoin('category_descriptions', function ($join) use ($languageId) {
                $join->on('category_descriptions.category_id', '=', 'categories.category_id')
                    ->where('category_descriptions.language_id', '=', $languageId);
            })
                ->leftJoin('categories_to_stores', 'categories_to_stores.category_id', '=', 'categories.category_id')
                ->where('categories.category_id', '=', $categoryId)
                ->where('categories_to_stores.store_id', '=', $storeId)
                ->first();

            if ($category) {
                $cache = $category->toArray();
                $cacheInst->push($cacheKey, $cache);
            }
        }
        return $cache;
    }

    /**
     * @param $parentId
     *
     * @return array|bool|false|mixed
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     */
    public static function getChildrenIDs($parentId)
    {
        $registry = Registry::getInstance();
        $config = $registry->get('config');
        $cacheInst = $registry->get('cache');

        $languageId = (int)$config->get('storefront_language_id');
        $storeId = (int)$config->get('config_store_id');
        $cacheKey = 'category.list.'.$parentId.'.store_'.$storeId.'_lang_'.$languageId;
        $cache = $cacheInst->pull($cacheKey);

        if ($cache === false) {
            $categories = self::select(['categories.category_id'])
                ->leftJoin('categories_to_stores', 'categories_to_stores.category_id', '=', 'categories.category_id');

            if ($parentId >= 0) {
                $categories = $categories->where('categories.parent_id', '=', $parentId);
            }
            $categories = $categories->where('categories_to_stores.store_id', '=', $storeId)
                ->active('categories')
                ->get();

            $cache = [];
            foreach ($categories as $category) {
                $cache[] = $category->category_id;
                $cache = array_merge($cache, self::getChildrenIDs($category->category_id));
            }
            $cacheInst->push($cacheKey, $cache);
        }
        return $cache;
    }

    /**
     * @return array|false|mixed
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     */
    public static function getAllCategories()
    {
        return self::getCategories(-1);
    }

    /**
     * @param null $parentId
     *
     * @return int
     */
    public static function getTotalCategoriesByCategoryId($parentId = null)
    {
        $registry = Registry::getInstance();
        $config = $registry->get('config');
        $storeId = 0;
        try {
            $storeId = $config->get('config_store_id');
        } catch (\Exception $e) {
            $registry->get('log')->write($e->getMessage());
        }

        $categoriesCount = 0;
        /** @var Category $categories */
        $categories = self::select(['categories.category_id'])
            ->leftJoin('categories_to_stores', 'categories_to_stores.category_id', '=', 'categories.category_id')
            ->where('categories_to_stores.store_id', '=', $storeId)
            ->active('categories');
        if ($parentId) {
            $categories = $categories->where('categories.parent_id', '=', $parentId);
        } else {
            $categories = $categories->whereNull('categories.parent_id');
        }
        if ($categories) {
            $categoriesCount = $categories->get()->count();
        }
        return $categoriesCount;
    }

    /**
     * @param $category_id
     *
     * @return string
     * @throws \Exception
     */
    public static function buildPath($category_id)
    {
        $categories = self::find((int)$category_id);
        if ($categories) {
            $categories = $categories->first(['category_id', 'parent_id']);
        }

        if ($categories && $categories->parent_id) {
            return self::buildPath($categories->parent_id).'_'.(string)$category_id;
        }
        return $category_id;
    }

    /**
     * @param $data
     *
     * @return array|bool
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     */
    public static function getCategoriesData($data)
    {
        $registry = Registry::getInstance();
        $db = $registry->get('db');
        $config = $registry->get('config');
        $cacheInst = $registry->get('cache');

        if ($data['language_id']) {
            $language_id = (int)$data['language_id'];
        } else {
            $language_id = (int)$registry->get('language')->getContentLanguageID();
        }

        if ($data['store_id']) {
            $store_id = (int)$data['store_id'];
        } else {
            $store_id = (int)$config->get('config_store_id');
        }

        $arSelect = [$db->raw('SQL_CALC_FOUND_ROWS  *')];

        if (ABC::env('IS_ADMIN')) {
            $arSelect[] = $db->raw('(SELECT count(*) as cnt
                       FROM '.$db->table_name('products_to_categories').' p
                       WHERE p.category_id = '.$db->table_name('categories').'.category_id) as products_count');
            $arSelect[] = $db->raw('(SELECT count(*) as cnt
                       FROM '.$db->table_name('categories').' cc
                       WHERE cc.parent_id = '.$db->table_name('categories').'.category_id) as subcategory_count,
                       '.$db->table_name('category_descriptions').'.name as basename');
        }
        $categories = self::select($arSelect)
            ->leftJoin('category_descriptions', function ($join) use ($language_id) {
                $join->on('category_descriptions.category_id', '=', 'categories.category_id')
                    ->where('category_descriptions.language_id', '=', $language_id);
            })
            ->join('categories_to_stores', function ($join) use ($store_id) {
                $join->on('categories_to_stores.category_id', '=', 'categories.category_id')
                    ->where('categories_to_stores.store_id', '=', $store_id);
            });

        $data['parent_id'] = (isset($data['parent_id']) && (int)$data['parent_id'] > 0) ? (int)$data['parent_id'] : null;

        $categories = $categories->where('categories.parent_id', '=', $data['parent_id']);

        if (!empty($data['subsql_filter'])) {
            $categories = $categories->whereRaw($data['subsql_filter']);
        }

        $sort_data = [
            'name'       => 'cd.name',
            'status'     => 'c.status',
            'sort_order' => 'c.sort_order',
        ];

        $desc = false;

        if (isset($data['sort']) && in_array($data['sort'], array_keys($sort_data))) {
            $sortBy = $data['sort'];
        } else {
            $sortBy = ['categories.sort_order', 'category_descriptions.name'];
        }

        if (isset($data['order']) && ($data['order'] === 'DESC')) {
            $desc = true;
        }

        if ($desc) {
            if (is_array($sortBy)) {
                foreach ($sortBy as $item) {
                    $categories = $categories->orderBy($item, 'desc');
                }
            } else {
                $categories = $categories->orderBy((string)$sortBy, 'desc');
            }
        } else {
            if (is_array($sortBy)) {
                foreach ($sortBy as $item) {
                    $categories = $categories->orderBy($item);
                }
            } else {
                $categories = $categories->orderBy((string)$sortBy);
            }
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $categories = $categories->limit($data['limit'])
                ->offset($data['start']);
        }

        $categories = $categories->get();

        if (!$categories) {
            return false;
        }

        $categories = $categories->toArray();
        $total_num_rows = $db->sql_get_row_count();

        $category_data = [];
        foreach ($categories as $result) {
            $result['total_num_rows'] = $total_num_rows;
            if ($data['basename'] === true) {
                $result['name'] = $result['basename'];
            } else {
                $result['name'] = self::getPath($result['category_id'], $language_id);
            }
            $category_data[] = $result;
        }
        return $category_data;
    }

    /**
     * @param $data
     *
     * @return bool|mixed
     * @throws \Exception
     */
    public static function addCategory($data)
    {
        /**
         * @var ADB $db
         */
        $db = Registry::db();
        $cache = Registry::cache();
        $registry = Registry::getInstance();

        $data['parent_id'] = (int)$data['parent_id'] > 0 ? (int)$data['parent_id'] : null;

        try {
            $db->beginTransaction();
            $category = new Category($data);
            $category->save();

            if (!$category) {
                return false;
            }

            $categoryId = $category->getKey();

            if ($data['category_description']) {
                foreach ($data['category_description'] as $languageId => $value) {
                    $arDescription = [
                        'language_id'      => $languageId,
                        'name'             => $value['name'] ?: '',
                        'meta_keywords'    => $value['meta_keywords'] ?: '',
                        'meta_description' => $value['meta_description'] ?: '',
                        'description'      => $value['description'] ?: '',
                    ];
                    $description = new CategoryDescription($arDescription);
                    $category->descriptions()->save($description);
                }
            }

            $categoryToStore = [];
            if (isset($data['category_store'])) {
                $db->table('categories_to_stores')
                    ->where('category_id', '=', (int)$categoryId)
                    ->delete();
                foreach ($data['category_store'] as $store_id) {
                    $categoryToStore[] = [
                        'category_id' => $categoryId,
                        'store_id'    => (int)$store_id,
                    ];
                }
            } else {
                $db->table('categories_to_stores')
                    ->where('category_id', '=', (int)$categoryId)
                    ->delete();
                $categoryToStore[] = [
                    'category_id' => $categoryId,
                    'store_id'    => 0,
                ];
            }
            $db->table('categories_to_stores')->insert($categoryToStore);

            $categoryName = '';
            if (isset($data['category_description'])) {
                $description = $data['category_description'];
                if (isset($description[$registry->get('language')->getContentLanguageID()]['name'])) {
                    $categoryName = $description[$registry->get('language')->getContentLanguageID()]['name'];
                }
            }

            UrlAlias::setCategoryKeyword($data['keyword'] ?: $categoryName, $categoryId);

            $db->commit();
        } catch (ValidationException $e) {
            $db->rollback();
            throw $e;
        } catch (\Exception $e) {
            $db->rollback();
            throw new AException(__CLASS__.': '.$e->getMessage(), 0, __FILE__);
        }

        $cache->remove('category');

        return $categoryId;
    }

    /**
     * @param $categoryId
     * @param $data
     *
     * @return bool
     * @throws AException
     * @throws ValidationException
     */
    public static function editCategory($categoryId, $data)
    {
        $db = Registry::db();
        $cache = Registry::cache();
        $registry = Registry::getInstance();

        if (isset($data['parent_id'])) {
            $data['parent_id'] = (int)$data['parent_id'] > 0 ? (int)$data['parent_id'] : null;
        }

        try {
            $db->beginTransaction();
            self::withTrashed()->find($categoryId)->update($data);

            if (!empty($data['category_description'])) {
                foreach ($data['category_description'] as $language_id => $value) {
                    $update = [];

                    foreach ($value as $key => $item_val) {
                        $update[$key] = $item_val;
                    }

                    if (!empty($update)) {
                        // insert or update
                        $registry->get('language')->replaceDescriptions('category_descriptions',
                            ['category_id' => (int)$categoryId],
                            [$language_id => $update]);
                    }
                }
            }

            $categoryToStore = [];
            if (isset($data['category_store'])) {
                $db->table('categories_to_stores')
                    ->where('category_id', '=', (int)$categoryId)
                    ->delete();

                foreach ($data['category_store'] as $store_id) {
                    $categoryToStore[] = [
                        'category_id' => $categoryId,
                        'store_id'    => (int)$store_id,
                    ];
                }
            } else {
                $db->table('categories_to_stores')
                    ->where('category_id', '=', (int)$categoryId)
                    ->delete();
                $categoryToStore[] = [
                    'category_id' => $categoryId,
                    'store_id'    => 0,
                ];
            }
            $db->table('categories_to_stores')->insert($categoryToStore);

            $categoryName = '';
            if (isset($data['category_description'])) {
                $description = $data['category_description'];
                if (isset($description[$registry->get('language')->getContentLanguageID()]['name'])) {
                    $categoryName = $description[$registry->get('language')->getContentLanguageID()]['name'];
                }
            }

            UrlAlias::setCategoryKeyword($data['keyword'] ?: $categoryName, (int)$categoryId);

            $db->commit();
        } catch (ValidationException $e) {
            $db->rollback();
            throw $e;
        } catch (\Exception $e) {
            $db->rollback();
            throw new AException(__CLASS__.': '.$e->getMessage(), 0, __FILE__);
        }

        $cache->remove('category');
        $cache->remove('product');

        return true;
    }

    /**
     * @param $categoryId
     *
     * @return bool
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     */
    public static function deleteCategory($categoryId)
    {
        $db = Registry::db();
        $registry = Registry::getInstance();
        $cacheInst = $registry->get('cache');

        $category = self::find((int)$categoryId);
        if (!$category) {
            return false;
        }
        try {
            $db->beginTransaction();
            /** @var Category $category */
            $category->delete();

            UrlAlias::where('query', '=', 'category_id='.(int)$categoryId)
                ->delete();

            /** @var AResourceManager $rm */
            $rm = new AResourceManager();
            try {
                $resources = $rm->getResourcesList(['object_name' => 'categories', 'object_id' => (int)$categoryId]);
            } catch (\Exception $exception) {
                $registry->get('log')->write($exception->getMessage());
            }
            foreach ($resources as $r) {
                try {
                    $rm->unmapResource('categories', $categoryId, $r['resource_id']);
                    //if resource became orphan - delete it
                    if (!$rm->isMapped($r['resource_id'])) {
                        $rm->deleteResource($r['resource_id']);
                    }
                } catch (\Exception $exception) {
                    $registry->get('log')->write($exception->getMessage());
                }
            }
            //remove layout
            $lm = new ALayoutManager();
            try {
                $lm->deletePageLayout('pages/product/category', 'path', $categoryId);
            } catch (\Exception $exception) {
                $registry->get('log')->write($exception->getMessage());
            }

            //delete children categories
            $subCategories = self::select(['category_id'])
                ->where('parent_id', '=', (int)$categoryId)
                ->get();
            if ($subCategories) {
                foreach ($subCategories as $result) {
                    self::deleteCategory($result->category_id);
                }
            }

            $db->commit();
        } catch (ValidationException $e) {
            $db->rollback();
            throw $e;
        } catch (\Exception $e) {
            $db->rollback();
            throw new AException(__CLASS__.': '.$e->getMessage(), 0, __FILE__);
        }

        $cacheInst->remove('category');
        $cacheInst->remove('product');
        return true;
    }

    /**
     * @return array
     */
    public static function getLeafCategories()
    {
        $categories = self::select(['categories.category_id'])
            ->leftJoin('categories as t2', 't2.parent_id', '=', 'categories.category_id')
            ->whereNull('t2.category_id')
            ->get();

        $result = [];
        if ($categories) {
            foreach ($categories as $category) {
                $result[$category->category_id] = $category->category_id;
            }
        }
        return $result;
    }

    /**
     * @param $category_id
     *
     * @return array
     */
    public static function getCategoryDescriptions($category_id)
    {
        $category_description_data = [];
        $categoryDecrs = CategoryDescription::where('category_id', '=', (int)$category_id)
            ->get();

        if (!$categoryDecrs) {
            return $category_description_data;
        }

        $categoryDecrs = $categoryDecrs->toArray();
        foreach ($categoryDecrs as $result) {
            $category_description_data[$result['language_id']] = [
                'name'             => $result['name'],
                'meta_keywords'    => $result['meta_keywords'],
                'meta_description' => $result['meta_description'],
                'description'      => $result['description'],
            ];
        }

        return $category_description_data;
    }

    /**
     * @param $category_id
     *
     * @return array
     */
    public static function getCategoryStores($category_id)
    {
        $registry = Registry::getInstance();
        $db = $registry->get('db');

        $stores = $db->table('categories_to_stores')
            ->where('category_id', '=', $category_id)
            ->get(['store_id']);

        $category_store_data = [];
        foreach ($stores as $result) {
            $category_store_data[] = $result->store_id;
        }

        return $category_store_data;
    }

    /**
     * @param $category_id
     *
     * @return array
     */
    public static function getCategoryStoresInfo($category_id)
    {
        $registry = Registry::getInstance();
        $db = $registry->get('db');
        $storeInfo = $db->table('categories_to_stores AS c2s')
            ->select(['c2s.*', 's.name AS store_name', 'ss.value AS store_url', 'sss.value AS store_ssl_url'])
            ->leftJoin('stores AS s', 's.store_id', '=', 'c2s.store_id')
            ->leftJoin('settings AS ss', function ($join) {
                $join->on('ss.store_id', '=', 'c2s.store_id')
                    ->where('ss.key', '=', 'config_url');
            })
            ->leftJoin('settings AS sss', function ($join) {
                $join->on('sss.store_id', '=', 'c2s.store_id')
                    ->where('sss.key', '=', 'config_ssl_url');
            })->where('category_id', '=', (int)$category_id)
            ->get();
        if ($storeInfo) {
            return json_decode($storeInfo, true);
        }
        return [];
    }

    /**
     * @return array|false|mixed
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     */
    public function getAllData()
    {
        $cache_key = 'category.alldata.'.$this->getKey();
        $data = $this->cache->pull($cache_key);
        if ($data === false) {
            $this->load('descriptions', 'stores');
            $data = $this->toArray();
            $data['images'] = $this->getImages();
            $data['keyword'] = UrlAlias::getCategoryKeyword($this->getKey(), $this->registry->get('language')->getContentLanguageID());
            $this->cache->push($cache_key, $data);
        }
        return $data;
    }

    /**
     * @return array
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     */
    public function getImages()
    {
        $images = [];
        $resource = new AResource('image');
        // main product image
        $sizes = [
            'main'  => [
                'width'  => $this->config->get('config_image_popup_width'),
                'height' => $this->config->get('config_image_popup_height'),
            ],
            'thumb' => [
                'width'  => $this->config->get('config_image_thumb_width'),
                'height' => $this->config->get('config_image_thumb_height'),
            ],
        ];
        $images['image_main'] = $resource->getResourceAllObjects('categories', $this->getKey(), $sizes, 1, false);
        if ($images['image_main']) {
            $images['image_main']['sizes'] = $sizes;
        }

        // additional images
        $sizes = [
            'main'   => [
                'width'  => $this->config->get('config_image_popup_width'),
                'height' => $this->config->get('config_image_popup_height'),
            ],
            'thumb'  => [
                'width'  => $this->config->get('config_image_additional_width'),
                'height' => $this->config->get('config_image_additional_height'),
            ],
            'thumb2' => [
                'width'  => $this->config->get('config_image_thumb_width'),
                'height' => $this->config->get('config_image_thumb_height'),
            ],
        ];
        $images['images'] = $resource->getResourceAllObjects('categories', $this->getKey(), $sizes, 0, false);
        if (!empty($images)) {
            $protocolSetting = Setting::select('value')->where('key', '=', 'protocol_url')->first();
            $protocol = 'http';
            if ($protocolSetting) {
                $protocol = $protocolSetting->value;
            }

            if (isset($images['image_main']['direct_url']) && strpos($images['image_main']['direct_url'], 'http') !== 0) {
                $images['image_main']['direct_url'] = $protocol.':'.$images['image_main']['direct_url'];
            }
            if (isset($images['image_main']['main_url']) && strpos($images['image_main']['main_url'], 'http') !== 0) {
                $images['image_main']['main_url'] = $protocol.':'.$images['image_main']['main_url'];
            }
            if (isset($images['image_main']['thumb_url']) && strpos($images['image_main']['thumb_url'], 'http') !== 0) {
                $images['image_main']['thumb_url'] = $protocol.':'.$images['image_main']['thumb_url'];
            }
            if (isset($images['image_main']['thumb2_url']) && strpos($images['image_main']['thumb2_url'], 'http') !== 0) {
                $images['image_main']['thumb2_url'] = $protocol.':'.$images['image_main']['thumb2_url'];
            }

            if ($images['images']) {
                foreach ($images['images'] as &$img) {
                    if (isset($img['direct_url']) && strpos($img['direct_url'], 'http') !== 0) {
                        $img['direct_url'] = $protocol.':'.$img['direct_url'];
                    }
                    if (isset($img['main_url']) && strpos($img['main_url'], 'http') !== 0) {
                        $img['main_url'] = $protocol.':'.$img['main_url'];
                    }
                    if (isset($img['thumb_url']) && strpos($img['thumb_url'], 'http') !== 0) {
                        $img['thumb_url'] = $protocol.':'.$img['thumb_url'];
                    }
                    if (isset($img['thumb2_url']) && strpos($img['thumb2_url'], 'http') !== 0) {
                        $img['thumb2_url'] = $protocol.':'.$img['thumb2_url'];
                    }
                }
            }

        }
        return $images;
    }

    /**
     * @param      $name
     * @param null $parent_id
     *
     * @return array
     */
    public static function getCategoryByName($name, $parent_id = null)
    {
        $db = Registry::db();
        $parent_id = (int)$parent_id;

        $name = $db->escape(
            mb_strtolower(
                html_entity_decode($name, ENT_QUOTES, ABC::env('APP_CHARSET'))
            )
        );

        $query = self::select(['category_descriptions.*', 'categories.*'])
            ->join('category_descriptions',
                'category_descriptions.category_id',
                '=',
                'categories.category_id')
            ->whereRaw('LOWER('.$db->table_name('category_descriptions').'.name) = \''.$name.'\'');

        if ($parent_id) {
            $query->where('parent_id', '=', $parent_id);
        } else {
            $query->whereNull('parent_id');
        }

        $result = $query->first();

        if (!$result) {
            return [];
        }
        return $result->toArray();
    }

}
