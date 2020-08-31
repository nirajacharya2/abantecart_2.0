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
use Carbon\Carbon;
use Dyrynda\Database\Support\GeneratesUuid;
use Exception;
use H;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;

/**
 * Class Category
 *
 * @property int $category_id
 * @property int $parent_id
 * @property string $path
 * @property string $uuid
 * @property int $total_products_count
 * @property int $active_products_count
 * @property int $children_count
 * @property int $sort_order
 * @property int $status
 * @property CategoryDescription $description
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @property Collection $categories_to_stores
 * @property Collection $category_descriptions
 * @property Collection $products_to_categories
 *
 * @method static Category find(int $customer_id) Category
 * @package abc\models
 */
class Category extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes, GeneratesUuid;

    protected $cascadeDeletes = [
        'descriptions',
        'products',
    ];
    /**
     * @var string
     */
    protected $primaryKey = 'category_id';

    /**
     * @var array
     */
    protected $casts = [
        'parent_id'             => 'int',
        'sort_order'            => 'int',
        'status'                => 'int',
        'total_products_count'  => 'int',
        'active_products_count' => 'int',
        'children_count'        => 'int',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $guarded = [
        'date_added',
        'date_modified',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'category_id',
        'uuid',
        'parent_id',
        'path',
        'total_products_count',
        'active_products_count',
        'children_count',
        'sort_order',
        'status',
    ];

    protected $rules = [
        /** @see validate() */
        'category_id' => [
            'checks'   => [
                'integer',
                'sometimes',
                'required',
            ],
            'messages' => [
                '*' => ['default_text' => 'Category ID is not integer!'],
            ],
        ],
        'parent_id'   => [
            'checks'   => [
                'integer',
                'nullable',
                'exists:categories,category_id',
            ],
            'messages' => [
                '*' => ['default_text' => 'Parent ID is not integer or absent in categories table!'],
            ],
        ],
        'uuid'        => [
            'checks'   => [
                'string',
                'nullable',
            ],
            'messages' => [
                '*' => ['default_text' => 'UUID is not a string!'],
            ],
        ],
        'path'        => [
            'checks'   => [
                'string',
                'sometimes',
                'required',
                'max:255',
            ],
            'messages' => [
                '*' => ['default_text' => 'Path must be a string less than 255 characters length!'],
            ],
        ],

        'total_products_count' => [
            'checks'   => [
                'integer',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer!',
                ],
            ],
        ],

        'active_products_count' => [
            'checks'   => [
                'integer',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer!',
                ],
            ],
        ],

        'children_count' => [
            'checks'   => [
                'integer',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer!',
                ],
            ],
        ],

        'sort_order' => [
            'checks'   => [
                'integer',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer!',
                ],
            ],
        ],

        'status' => [
            'checks'   => [
                'boolean',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not boolean!',
                ],
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
                    ->where('language_id', '=', static::$current_language_id);
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
     * @return array|false|mixed
     * @throws ReflectionException
     * @throws AException
     * @throws InvalidArgumentException
     */
    public function getAllData()
    {
        $cache_key = 'category.alldata.'.$this->getKey();
        $data = $this->cache->get($cache_key);
        if ($data === null) {
            $this->load('descriptions', 'stores');
            $data = $this->toArray();
            $data['images'] = $this->getImages();
            if ($this->getKey()) {
                $data['keywords'] = UrlAlias::getKeyWordsArray($this->getKeyName(), $this->getKey());
            }
            $this->cache->put($cache_key, $data);
        }
        return $data;
    }

    /**
     * @return array
     * @throws ReflectionException
     * @throws AException
     * @throws InvalidArgumentException
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

            if (isset($images['image_main']['direct_url'])
                && strpos($images['image_main']['direct_url'], 'http') !== 0) {
                $images['image_main']['direct_url'] = $protocol.':'.$images['image_main']['direct_url'];
            }
            if (isset($images['image_main']['main_url']) && strpos($images['image_main']['main_url'], 'http') !== 0) {
                $images['image_main']['main_url'] = $protocol.':'.$images['image_main']['main_url'];
            }
            if (isset($images['image_main']['thumb_url']) && strpos($images['image_main']['thumb_url'], 'http') !== 0) {
                $images['image_main']['thumb_url'] = $protocol.':'.$images['image_main']['thumb_url'];
            }
            if (isset($images['image_main']['thumb2_url'])
                && strpos($images['image_main']['thumb2_url'], 'http') !== 0) {
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
     * @param        $category_id
     * @param string $mode
     *
     * @return string
     * @throws ReflectionException
     * @throws AException
     * @throws InvalidArgumentException
     */
    public static function getPath($category_id, $mode = '')
    {

        $query = Category::where('categories.category_id', '=', (int)$category_id)
                         ->orderBy('categories.sort_order');
        if ($mode != 'id') {
            $query->leftJoin(
                'category_descriptions',
                'categories.category_id',
                '=',
                'category_descriptions.category_id'
            )
                  ->where('category_descriptions.language_id', '=', static::$current_language_id)
                  ->orderBy('category_descriptions.name');
        }
        $categories = $query->get()->toArray();

        $category_info = current($categories);

        if ($category_info['parent_id']) {
            if ($mode == 'id') {
                return static::getPath(
                        $category_info['parent_id'],
                        $mode
                    )
                    .'_'
                    .$category_info['category_id'];
            } else {
                return static::getPath(
                        $category_info['parent_id'],
                        $mode
                    )
                    .Registry::language()->get('text_separator')
                    .$category_info['name'];
            }
        } else {
            return $mode == 'id' ? $category_id : $category_info['name'];
        }
    }

    /**
     * @param        $category_id
     *
     * @return array
     * @throws ReflectionException
     * @throws AException
     * @throws InvalidArgumentException
     */
    public static function getCategoryBranchInfo($category_id)
    {
        $category_id = (int)$category_id;
        if (!$category_id) {
            return [];
        }

        $childrenIDs = $children = static::getChildrenIDs($category_id);
        $childrenIDs[] = $category_id;

        $p2cAlias = Registry::db()->table_name('products_to_categories');
        $pAlias = Registry::db()->table_name('products');
        /** @var QueryBuilder $query */
        $query = Category::select('parent_id')
                         ->where('categories.category_id', '=', $category_id)
                         ->selectRaw(
                             '(SELECT COUNT('.$pAlias.'.product_id)
            FROM '.$pAlias.'
            INNER JOIN '.$p2cAlias.'
                ON ('.$p2cAlias.'.product_id = '.$pAlias.'.product_id)
            WHERE '.$pAlias.'.status = 1 
                    AND COALESCE('.$pAlias.'.date_available, NOW()) <= NOW()
                    AND '.$pAlias.'.date_deleted IS NULL
                    AND '.$p2cAlias.'.category_id IN ('.implode(", ", $childrenIDs).')
            ) as active_products_count'
                         )->selectRaw(
                '(SELECT COUNT('.$pAlias.'.product_id)
            FROM '.$pAlias.'
            INNER JOIN '.$p2cAlias.'
                ON ('.$p2cAlias.'.product_id = '.$pAlias.'.product_id)
            WHERE '.$p2cAlias.'.category_id IN ('.implode(", ", $childrenIDs).')
                AND '.$pAlias.'.date_deleted IS NULL
            ) as total_products_count'
            );

        $category_info = $query->distinct()->first();

        return [
            'path'                  => static::getPath($category_id, 'id'),
            'children'              => $children,
            'active_products_count' => (int)$category_info->active_products_count,
            'total_products_count'  => (int)$category_info->total_products_count,
        ];
    }

    /**
     * @param int $parentId
     * @param null $storeId
     * @param int $limit
     *
     * @return array
     * @throws ReflectionException
     * @throws AException
     * @throws InvalidArgumentException
     */
    public static function getCategories($parentId = 0, $storeId = null, $limit = 0)
    {
        $languageId = static::$current_language_id;

        $cacheKey = 'category.list.'.$parentId
            .'.store_'.$storeId
            .'_limit_'.$limit
            .'_lang_'.$languageId
            .'_side_'.(int)ABC::env('IS_ADMIN');
        $cache = Registry::cache()->get($cacheKey);

        if ($cache === null) {

            $category_data = [];

            /**
             * @var QueryBuilder $query
             */
            $query = static::select();
            $query->leftJoin(
                'category_descriptions',
                'categories.category_id',
                '=',
                'category_descriptions.category_id'
            );
            if (!is_null($storeId)) {
                $query->rightJoin(
                    'categories_to_stores',
                    function ($join) use ($storeId) {
                        /** @var JoinClause $join */
                        $join->on(
                            'categories.category_id',
                            '=',
                            'categories_to_stores.category_id'
                        )->where('categories_to_stores.store_id', '=', (int)$storeId);
                    });
            }

            if ((int)$parentId > 0) {
                $query->where('categories.parent_id', '=', (int)$parentId);
            } else {
                $query->whereNull('categories.parent_id');
            }

            $query
                ->where('category_descriptions.language_id', '=', $languageId);
            if (!ABC::env('IS_ADMIN')) {
                $query->active('categories');
            }
            $query->orderBy('categories.sort_order')
                  ->orderBy('category_descriptions.name');

            //allow to extends this method from extensions
            Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $query, func_get_args());
            $categories = $query->get();

            foreach ($categories as $category) {
                if (ABC::env('IS_ADMIN')) {
                    $category->name = static::getPath($category->category_id);
                }
                $category_data[] = $category->toArray();
                $category_data = array_merge($category_data, static::getCategories($category->category_id, $storeId));
            }
            $cache = $category_data;
            Registry::cache()->put($cacheKey, $cache);
        }
        return $cache;
    }

    /**
     * @param int $categoryId
     *
     * @return false|mixed
     * @throws InvalidArgumentException
     */
    public static function getCategory($categoryId)
    {
        $db = Registry::db();
        $storeId = (int)Registry::config()->get('config_store_id');
        $languageId = static::$current_language_id;

        $cacheKey = 'product.listing.category.'.(int)$categoryId.'.store_'.$storeId.'_lang_'.$languageId;
        $cache = Registry::cache()->get($cacheKey);
        if ($cache === null) {

            $arSelect = ['*'];

            if (ABC::env('IS_ADMIN')) {
                $arSelect[] = $db->raw(
                    "(SELECT keyword 
                      FROM ".$db->table_name("url_aliases")
                    ." WHERE query = 'category_id=".$categoryId."' 
                                AND language_id='".$languageId."' ) as keyword"
                );
            } else {
                $arSelect[] = $db->raw(
                    "(SELECT COUNT(p2c.product_id) as cnt
                      FROM ".$db->table_name('products_to_categories')." p2c
                      INNER JOIN ".$db->table_name('products')." p 
                         ON p.product_id = p2c.product_id AND p.status = '1'
                      WHERE  p2c.category_id = ".$db->table_name('categories').".category_id
                     ) as products_count");
            }
            /** @var Collection|QueryBuilder $query */
            $query = self::select($arSelect);
            $query->leftJoin(
                'category_descriptions',
                function ($join) use ($languageId) {
                    /** @var JoinClause $join */
                    $join->on('category_descriptions.category_id', '=', 'categories.category_id')
                         ->where('category_descriptions.language_id', '=', $languageId);
                })
                  ->leftJoin(
                      'categories_to_stores',
                      'categories_to_stores.category_id',
                      '=',
                      'categories.category_id'
                  )
                  ->where('categories.category_id', '=', $categoryId)
                  ->where('categories_to_stores.store_id', '=', $storeId);
            //allow to extends this method from extensions
            Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $query, func_get_args());
            $category = $query->first();

            if ($category) {
                $cache = $category->toArray();
                Registry::cache()->put($cacheKey, $cache);
            }
        }
        return $cache;
    }

    /**
     * @param int $categoryId
     *
     * @param string $mode - can be empty or "active_only"
     *
     * @return array
     */
    public static function getChildrenIDs($categoryId, $mode = '')
    {
        $categoryId = (int)$categoryId;
        if (!$categoryId) {
            return [];
        }

        $storeId = (int)Registry::config()->get('config_store_id');

        /** @var QueryBuilder|Collection $query */
        $query = self::select(['categories.category_id']);
        $query->leftJoin(
            'categories_to_stores',
            'categories_to_stores.category_id',
            '=',
            'categories.category_id'
        );
        $query->where('categories.parent_id', '=', $categoryId);
        $query->where('categories_to_stores.store_id', '=', $storeId);
        if ($mode == 'active_only') {
            $query->active('categories');
        }

        //allow to extends this method from extensions
        Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $query, func_get_args());
        $categories = $query->get();
        $output = [];
        foreach ($categories as $category) {
            $output[] = $category->category_id;
            $output = array_merge($output, static::getChildrenIDs($category->category_id));
        }

        return $output;
    }

    /**
     * @return array
     * @throws ReflectionException
     * @throws AException
     * @throws InvalidArgumentException
     */
    public static function getAllCategories()
    {
        return static::getCategories(-1);
    }

    /**
     * @param null|int $parentId
     *
     * @return int
     */
    public static function getTotalCategoriesByCategoryId($parentId = null)
    {
        /** @var QueryBuilder|Collection $query */
        $query = self::select(['categories.category_id']);
        $query->leftJoin(
            'categories_to_stores',
            'categories_to_stores.category_id',
            '=',
            'categories.category_id'
        )
              ->where('categories_to_stores.store_id', '=', (int)Registry::config()->get('config_store_id'))
              ->active('categories');
        if ($parentId) {
            $query->where('categories.parent_id', '=', $parentId);
        } else {
            $query->whereNull('categories.parent_id');
        }

        //allow to extends this method from extensions
        Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $query, func_get_args());
        return $query->get()->count();
    }

    /**
     * @param $inputData
     *
     * @return Collection|bool
     * @throws ReflectionException
     * @throws AException
     * @throws InvalidArgumentException
     */
    public static function getCategoriesData($inputData)
    {
        $db = Registry::db();
        if ($inputData['language_id']) {
            $language_id = (int)$inputData['language_id'];
        } else {
            $language_id = static::$current_language_id;
        }

        if ($inputData['store_id']) {
            $store_id = (int)$inputData['store_id'];
        } else {
            $store_id = (int)Registry::config()->get('config_store_id');
        }

        $arSelect = [];
        if (ABC::env('IS_ADMIN')) {
            $arSelect[] = 'category_descriptions.name as basename';
        }
        /** @var QueryBuilder $query */
        $query = self::selectRaw(Registry::db()->raw_sql_row_count().' '.$db->table_name('categories').'.*')
                     ->addSelect($arSelect);
        $query->leftJoin('category_descriptions', function ($join) use ($language_id) {
            /** @var JoinClause $join */
            $join->on('category_descriptions.category_id', '=', 'categories.category_id')
                 ->where('category_descriptions.language_id', '=', $language_id);
        })
              ->join(
                  'categories_to_stores',
                  function ($join) use ($store_id) {
                      /** @var JoinClause $join */
                      $join->on('categories_to_stores.category_id', '=', 'categories.category_id')
                           ->where('categories_to_stores.store_id', '=', $store_id);
                  });

        $inputData['parent_id'] =
            (isset($inputData['parent_id']) && (int)$inputData['parent_id'] > 0) ? (int)$inputData['parent_id'] : null;

        $query->where('categories.parent_id', '=', $inputData['parent_id']);

        if (H::has_value($inputData['status'])) {
            $query->where('categories.status', '=', (int)$inputData['status']);
        }
        //include ids set
        if (H::has_value($inputData['include'])) {
            $filter['include'] = (array)$inputData['include'];
            foreach ($filter['include'] as &$id) {
                $id = (int)$id;
            }
            $query->whereIn('categories.category_id', $filter['include']);
        }
        //exclude already selected in chosen element
        if (H::has_value($inputData['exclude'])) {
            $filter['exclude'] = (array)$inputData['exclude'];
            foreach ($filter['exclude'] as &$id) {
                $id = (int)$id;
            }
            $query->whereNotIn('categories.category_id', $filter['exclude']);
        }

        if (H::has_value($inputData['name'])) {
            $query->where(function ($query) use ($inputData) {
                /** @var QueryBuilder $query */
                if ($inputData['search_operator'] == 'equal') {
                    $query->orWhere(
                        'category_descriptions.name',
                        '=',
                        mb_strtolower($inputData['name'])
                    );
                    $query->orWhere(
                        'category_descriptions.description',
                        '=',
                        mb_strtolower($inputData['name'])
                    );
                    $query->orWhere(
                        'category_descriptions.meta_keywords',
                        '=',
                        mb_strtolower($inputData['name'])
                    );
                } else {
                    $query->orWhere(
                        'category_descriptions.name',
                        'like',
                        "%".mb_strtolower($inputData['name'])."%"
                    );
                    $query->orWhere(
                        'category_descriptions.description',
                        'like',
                        "%".mb_strtolower($inputData['name'])."%"
                    );
                    $query->orWhere(
                        'category_descriptions.meta_keywords',
                        'like',
                        "%".mb_strtolower($inputData['name'])."%"
                    );
                }

            });
        }

        $sort_data = [
            'name'       => 'category_descriptions.name',
            'status'     => 'categories.status',
            'sort_order' => 'categories.sort_order',
        ];

        $desc = false;

        if (isset($inputData['sort']) && in_array($inputData['sort'], array_keys($sort_data))) {
            $sortBy = $inputData['sort'];
        } else {
            $sortBy = 'categories.sort_order';
        }

        if (isset($inputData['order']) && ($inputData['order'] == 'DESC')) {
            $desc = true;
        }

        if ($desc) {
            if (is_array($sortBy)) {
                foreach ($sortBy as $item) {
                    $query->orderBy($item, 'desc');
                }
            } else {
                $query->orderBy($sortBy, 'desc');
            }
        } else {
            if (is_array($sortBy)) {
                foreach ($sortBy as $item) {
                    $query->orderBy($item);
                }
            } else {
                $query->orderBy($sortBy);
            }
        }

        if (isset($inputData['start']) || isset($inputData['limit'])) {
            if ($inputData['start'] < 0) {
                $inputData['start'] = 0;
            }

            if ($inputData['limit'] < 1) {
                $inputData['limit'] = 20;
            }

            $query->limit($inputData['limit'])
                  ->offset($inputData['start']);
        }

        //allow to extends this method from extensions
        Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $query, $inputData);
        $result_rows = $query->get();
        $total_num_rows = Registry::db()->sql_get_row_count();
        foreach ($result_rows as &$result) {
            $result['total_num_rows'] = $total_num_rows;
            if ($inputData['basename'] == true) {
                $result->name = $result->basename;
            } else {
                $result->name = static::getPath($result->category_id, 'name');
            }
        }
        return $result_rows;
    }

    /**
     * @param $data
     *
     * @return bool|mixed
     * @throws Exception
     */
    public static function addCategory($data)
    {
        $db = Registry::db();
        $data['parent_id'] = (int)$data['parent_id'] > 0 ? (int)$data['parent_id'] : null;
        $db->beginTransaction();
        $category = null;
        try {
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
                if (isset($description[static::$current_language_id]['name'])) {
                    $categoryName = $description[static::$current_language_id]['name'];
                }
            }
            //allow to extends this method from extensions
            Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $category, func_get_args());
            if ($data['keywords']) {
                UrlAlias::replaceKeywords($data['keywords'], $category->getKeyName(), $category->getKey());
            } elseif ($data['keyword']) {
                UrlAlias::setCategoryKeyword(($data['keyword'] ?: $categoryName), (int)$categoryId);
            }

            Registry::cache()->flush('category');
            $db->commit();
            //call listener on saved event after commit
            $category->touch();
            return $categoryId;
        } catch (Exception $e) {
            Registry::log()->write($e->getMessage());
            $db->rollback();
            return false;
        }
    }

    /**
     * @param $categoryId
     * @param $data
     *
     * @return bool
     */
    public static function editCategory($categoryId, $data)
    {
        $db = Registry::db();
        $language = Registry::language();
        $cache = Registry::cache();
        $db->beginTransaction();

        try {
            if (isset($data['parent_id'])) {
                $data['parent_id'] = (int)$data['parent_id'] > 0 ? (int)$data['parent_id'] : null;
            }

            $category = self::withTrashed()->find($categoryId);
            $category->update($data);

            if (!empty($data['category_description'])) {
                foreach ($data['category_description'] as $language_id => $value) {
                    if (!$value) {
                        continue;
                    }
                    $value['language_id'] = $language_id;
                    $category->descriptions()->update($value);
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
            $db->table('categories_to_stores')
               ->insert($categoryToStore);

            $categoryName = '';
            if (isset($data['category_description'])) {
                $description = $data['category_description'];
                if (isset($description[$language->getContentLanguageID()]['name'])) {
                    $categoryName = $description[$language->getContentLanguageID()]['name'];
                }
            }
            if ($data['keywords']) {
                UrlAlias::replaceKeywords($data['keywords'], $category->getKeyName(), $category->getKey());
            } elseif ($data['keyword']) {
                UrlAlias::setCategoryKeyword(($data['keyword'] ?: $categoryName), (int)$categoryId);
            }

            //allow to extends this method from extensions
            Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $category, func_get_args());

            $cache->flush('category');
            $cache->flush('product');
            $db->commit();
            //call event listener on saved
            $category->touch();
            return true;
        } catch (Exception $e) {
            $db->rollback();
            Registry::log()->write(__CLASS__." ".$e->getMessage()."\n".$e->getTraceAsString());
            return false;
        }
    }

    /**
     * @param $categoryId
     *
     * @return bool
     * @throws AException
     * @throws ReflectionException
     * @throws InvalidArgumentException
     */
    public static function deleteCategory($categoryId)
    {
        /** @var Category $category */
        $category = self::find((int)$categoryId);
        $cache = Registry::cache();

        if (!$category) {
            throw new Exception('Cannot to find category ID '.$categoryId);
        }

        //run recalculation of products count before delete
        //(in case with data inconsistency)
        $category->touch();
        $category->refresh();

        //do not allow non empty category
        if ($category->total_products_count) {
            throw new Exception('Cannot to delete category ID '.$categoryId.'. It have '.$category->total_products_count
                .' products!');
        }

        //get all children of category by tree and add current
        $subCategories = self::getChildrenIDs((int)$categoryId);
        $subCategories[] = $categoryId;

        foreach ($subCategories as $categoryId) {
            UrlAlias::where(
                'query',
                '=',
                'category_id='.(int)$categoryId
            )->forceDelete();

            //delete resources
            $rm = new AResourceManager();
            $resources = $rm->getResourcesList(
                [
                    'object_name' => 'categories',
                    'object_id'   => (int)$categoryId,
                ]
            );

            foreach ($resources as $r) {
                $rm->unmapResource('categories', $categoryId, $r['resource_id']);
                //if resource became orphan - delete it
                if (!$rm->isMapped($r['resource_id'])) {
                    $rm->deleteResource($r['resource_id']);
                }
            }
            //remove layout
            $lm = new ALayoutManager();
            $lm->deletePageLayout('pages/product/category', 'path', $categoryId);
            $category = static::find($categoryId);
            $parentId = null;
            if ($category) {
                $parentId = $category->parent_id;
                //allow to extends this method from extensions
                Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $category, func_get_args());
                $category->forceDelete();
            }
            if ($parentId) {
                $parent = Category::find($parentId);
                if ($parent) {
                    //run recalculation of products count and subcategories count
                    $parent->touch();
                }
            }
        }

        $cache->flush('category');
        $cache->flush('product');
        return true;
    }

    /**
     * @return array
     */
    public static function getLeafCategories()
    {
        /** @var QueryBuilder $query */
        $query = self::select(['categories.category_id']);
        $query->leftJoin(
            'categories as t2',
            't2.parent_id',
            '=',
            'categories.category_id'
        )->whereNull('t2.category_id');

        $categories = $query->get();

        $result = [];
        if ($categories) {
            return array_column($categories->toArray(), 'category_id', 'category_id');
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
        $categoryDescriptions = CategoryDescription::where('category_id', '=', (int)$category_id)->get();

        if (!$categoryDescriptions) {
            return $category_description_data;
        }

        $categoryDescriptions = $categoryDescriptions->toArray();
        foreach ($categoryDescriptions as $result) {
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
        $stores = Registry::db()->table('categories_to_stores')
                          ->where('category_id', '=', $category_id)
                          ->get(['store_id']);

        if ($stores) {
            return array_column($stores->toArray(), 'store_id');
        }

        return [];
    }

    /**
     * @param $category_id
     *
     * @return array
     */
    public static function getCategoryStoresInfo($category_id)
    {

        $storeInfo = Registry::db()->table('categories_to_stores AS c2s')
                             ->select(
                                 [
                                     'c2s.*',
                                     's.name AS store_name',
                                     'ss.value AS store_url',
                                     'sss.value AS store_ssl_url',
                                 ]
                             )->leftJoin(
                'stores AS s',
                's.store_id',
                '=',
                'c2s.store_id'
            )->leftJoin(
                'settings AS ss',
                function ($join) {
                    /** @var JoinClause $join */
                    $join->on('ss.store_id', '=', 'c2s.store_id')
                         ->where('ss.key', '=', 'config_url');
                }
            )->leftJoin(
                'settings AS sss',
                function ($join) {
                    /** @var JoinClause $join */
                    $join->on('sss.store_id', '=', 'c2s.store_id')
                         ->where('sss.key', '=', 'config_ssl_url');
                }
            )->where('category_id', '=', (int)$category_id)
                             ->get();
        if ($storeInfo) {
            return json_decode($storeInfo, true);
        }
        return [];
    }

    /**
     * @param $category_id
     *
     * @return array
     */
    public static function getCategoryInfo($category_id)
    {
        $category_id = (int)$category_id;
        $language_id = static::$current_language_id;

        /** @var QueryBuilder $query */
        $query = static::select(["category_descriptions", 'categories.*']);
        $query->selectRaw("(SELECT keyword
                            FROM ".Registry::db()->table_name("url_aliases")." 
                            WHERE query = 'category_id=".$category_id."'
                            AND language_id='".$language_id."' ) as keyword");
        $query->leftJoin(
            'category_descriptions',
            'category_descriptions.category_id',
            '=',
            'categories.category_id'
        );

        $query->where(
            [
                'categories.category_id'            => $category_id,
                'category_descriptions.language_id' => $language_id,
            ]
        );

        $query->orderBy('categories.sort_order');
        $query->orderBy('category_descriptions.name');

        //allow to extends this method from extensions
        Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $query, func_get_args());
        return $query->get()->toArray();
    }

    /**
     * @param string $name
     * @param int|null $parent_id
     *
     * @return QueryBuilder|BaseModel|null
     */
    public static function getCategoryByName(string $name, $parent_id = null)
    {
        $db = Registry::db();
        $name = $db->escape(mb_strtolower(html_entity_decode($name, ENT_QUOTES, ABC::env('APP_CHARSET'))));
        /** @var QueryBuilder $query */
        $query = CategoryDescription::whereRaw("LOWER(name) = '".$name."'");
        $query->join(
            'categories',
            'categories.category_id',
            '=',
            'category_descriptions.category_id'
        );
        $query->addSelect('category_descriptions.*');
        $query->addSelect('categories.*');
        $parent_id = (int)$parent_id;
        if (!$parent_id) {
            $query->whereNull('categories.parent_id');
        } else {
            $query->where(['categories.parent_id', $parent_id]);
        }

        //allow to extends this method from extensions
        Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $query, func_get_args());
        return $query->first();
    }
}
