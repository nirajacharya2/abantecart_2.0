<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2022 Belavier Commerce LLC
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
use Error;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @property Collection $categories_to_stores
 * @property CategoryDescription $description
 * @property CategoryDescription $descriptions
 * @property Collection $products_to_categories
 *
 * @package abc\models
 */
class Category extends BaseModel
{
    use GeneratesUuid;

    protected $cascadeDeletes = [
        'descriptions',
        'products',
    ];
    /** @var string */
    protected $primaryKey = 'category_id';

    /** @var array */
    protected $casts = [
        'parent_id'             => 'int',
        'sort_order'            => 'int',
        'status'                => 'int',
        'total_products_count'  => 'int',
        'active_products_count' => 'int',
        'children_count'        => 'int',
        'date_added'            => 'datetime',
        'date_modified'         => 'datetime'
    ];

    protected $hidden = ['pivot'];

    protected $guarded = [
        'date_added',
        'date_modified',
    ];

    /** @var array */
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
     * @return HasMany
     */
    public function descriptions()
    {
        return $this->hasMany(CategoryDescription::class, 'category_id');
    }

    /**
     * @return HasOne
     */
    public function description()
    {
        return $this->hasOne(CategoryDescription::class, 'category_id', 'category_id')
            ->where('language_id', '=', static::$current_language_id);
    }

    /**
     * @return BelongsToMany
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'products_to_categories', 'category_id', 'product_id');
    }

    /**
     * @return BelongsToMany
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
        if (!$this->getKey()) {
            return false;
        }
        $cacheKey = 'category.alldata.' . $this->getKey();
        $data = Registry::cache()->get($cacheKey);
        if ($data !== null) {
            return $data;
        }

        // eagerLoading!
        $toLoad = $nested = [];
        $rels = $this->getRelationships('HasMany', 'HasOne', 'belongsToMany');
        foreach ($rels as $relName => $rel) {
            if (in_array($relName, ['products', 'description'])) {
                continue;
            }
            if ($rel['getAllData']) {
                $nested[] = $relName;
            } else {
                $toLoad[] = $relName;
            }
        }

        $this->load($toLoad);
        $data = $this->toArray();
        foreach ($nested as $prop) {
            foreach ($this->{$prop} as $option) {
                /** @var ProductOption $option */
                $data[$prop][] = $option->getAllData();
            }
        }

        $data['images'] = $this->images();
        $data['keywords'] = UrlAlias::getKeyWordsArray($this->getKeyName(), $this->getKey());
        Registry::cache()->put($cacheKey, $data);
        return $data;
    }

    /**
     * @return array
     * @throws ReflectionException
     * @throws AException
     * @throws InvalidArgumentException
     */
    public function images()
    {
        $config = Registry::config();
        $images = [];
        $resource = new AResource('image');
        // main product image
        $sizes = [
            'main'  => [
                'width'  => $config->get('config_image_popup_width'),
                'height' => $config->get('config_image_popup_height'),
            ],
            'thumb' => [
                'width'  => $config->get('config_image_thumb_width'),
                'height' => $config->get('config_image_thumb_height'),
            ],
        ];
        $images['image_main'] = $resource->getResourceAllObjects('categories', $this->getKey(), $sizes, 1, false);
        if ($images['image_main']) {
            $images['image_main']['sizes'] = $sizes;
        }

        // additional images
        $sizes = [
            'main'   => [
                'width'  => $config->get('config_image_popup_width'),
                'height' => $config->get('config_image_popup_height'),
            ],
            'thumb'  => [
                'width'  => $config->get('config_image_additional_width'),
                'height' => $config->get('config_image_additional_height'),
            ],
            'thumb2' => [
                'width'  => $config->get('config_image_thumb_width'),
                'height' => $config->get('config_image_thumb_height'),
            ],
        ];
        $images['images'] = $resource->getResourceAllObjects('categories', $this->getKey(), $sizes, 0, false);
        if (!empty($images)) {
            /** @var Setting $protocolSetting */
            $protocolSetting = Setting::select('value')->where('key', '=', 'protocol_url')->first();
            $protocol = 'http';
            if ($protocolSetting) {
                $protocol = $protocolSetting->value;
            }

            if (isset($images['image_main']['direct_url'])
                && !str_starts_with($images['image_main']['direct_url'], 'http')
            ) {
                $images['image_main']['direct_url'] = $protocol . ':' . $images['image_main']['direct_url'];
            }
            if (isset($images['image_main']['main_url'])
                && !str_starts_with($images['image_main']['main_url'], 'http')
            ) {
                $images['image_main']['main_url'] = $protocol . ':' . $images['image_main']['main_url'];
            }
            if (isset($images['image_main']['thumb_url'])
                && !str_starts_with($images['image_main']['thumb_url'], 'http')
            ) {
                $images['image_main']['thumb_url'] = $protocol . ':' . $images['image_main']['thumb_url'];
            }
            if (isset($images['image_main']['thumb2_url'])
                && !str_starts_with($images['image_main']['thumb2_url'], 'http')
            ) {
                $images['image_main']['thumb2_url'] = $protocol . ':' . $images['image_main']['thumb2_url'];
            }

            if ($images['images']) {
                foreach ($images['images'] as &$img) {
                    if (isset($img['direct_url']) && !str_starts_with($img['direct_url'], 'http')) {
                        $img['direct_url'] = $protocol . ':' . $img['direct_url'];
                    }
                    if (isset($img['main_url']) && !str_starts_with($img['main_url'], 'http')) {
                        $img['main_url'] = $protocol . ':' . $img['main_url'];
                    }
                    if (isset($img['thumb_url']) && !str_starts_with($img['thumb_url'], 'http')) {
                        $img['thumb_url'] = $protocol . ':' . $img['thumb_url'];
                    }
                    if (isset($img['thumb2_url']) && !str_starts_with($img['thumb2_url'], 'http')) {
                        $img['thumb2_url'] = $protocol . ':' . $img['thumb2_url'];
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
        $categories = $query->useCache('category')->get()->toArray();

        $category_info = current($categories);

        if ($category_info['parent_id']) {
            if ($mode == 'id') {
                return static::getPath(
                        $category_info['parent_id'],
                        $mode
                    )
                    . '_'
                    . $category_info['category_id'];
            } else {
                return static::getPath(
                        $category_info['parent_id'],
                        $mode
                    )
                    . Registry::language()->get('text_separator')
                    . $category_info['name'];
            }
        } else {
            return $mode == 'id' ? $category_id : $category_info['name'];
        }
    }

    /**
     * @param int $category_id
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
        $query = Category::select('parent_id')
            ->where('categories.category_id', '=', $category_id)
            ->selectRaw(
                '(SELECT COUNT(DISTINCT ' . $pAlias . '.product_id)
                    FROM ' . $pAlias . '
                    INNER JOIN ' . $p2cAlias . '
                        ON (' . $p2cAlias . '.product_id = ' . $pAlias . '.product_id)
                    WHERE ' . $pAlias . '.status = 1 
                            AND COALESCE(' . $pAlias . '.date_available, NOW()) <= NOW()
                            AND ' . $p2cAlias . '.category_id IN (' . implode(", ", $childrenIDs) . ')
                    ) as active_products_count'
            )->selectRaw(
                '(SELECT COUNT(DISTINCT ' . $pAlias . '.product_id)
                    FROM ' . $pAlias . '
                    INNER JOIN ' . $p2cAlias . '
                        ON (' . $p2cAlias . '.product_id = ' . $pAlias . '.product_id)
                    WHERE ' . $p2cAlias . '.category_id IN (' . implode(", ", $childrenIDs) . ')
                    ) as total_products_count'
            );

        /** @var Category $category_info */
        $category_info = $query->distinct()->first();

        return [
            'path'                  => static::getPath($category_id, 'id'),
            'children'              => $children,
            'active_products_count' => $category_info->active_products_count,
            'total_products_count'  => $category_info->total_products_count,
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
    public static function getCategories($parentId = 0, $storeId = null, $limit = 0, $languageId = null)
    {
        $languageId = $languageId ?? static::$current_language_id;

        $category_data = [];

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
                }
            );
        }

        if ((int)$parentId > 0) {
            $query->where('categories.parent_id', '=', (int)$parentId);
        } else {
            $query->whereNull('categories.parent_id');
        }

        $query->where('category_descriptions.language_id', '=', $languageId);
        if (!ABC::env('IS_ADMIN')) {
            $query->active('categories');
        }
        $query->orderBy('categories.sort_order')
            ->orderBy('category_descriptions.name');

        //allow to extend this method from extensions
        Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $query, func_get_args());
        $categories = $query->useCache('category')->get();

        foreach ($categories as $category) {
            if (ABC::env('IS_ADMIN')) {
                $category->name = static::getPath($category->category_id);
            }
            $category_data[] = $category->toArray();
            $category_data = array_merge($category_data, static::getCategories($category->category_id, $storeId, $limit, $languageId));
        }


        return $category_data;
    }

    /**
     * @param int $categoryId
     *
     * @return false|mixed
     */
    public static function getCategory($categoryId, $storeId = null, $limit = 0, $languageId = null)
    {
        $db = Registry::db();
        $storeId = $storeId ?? (int)Registry::config()->get('config_store_id');
        $languageId = $languageId ?? static::$current_language_id;

        $arSelect = ['*'];

        if (ABC::env('IS_ADMIN')) {
            $arSelect[] = $db->raw(
                "(SELECT keyword 
                      FROM " . $db->table_name("url_aliases")
                . " WHERE query = 'category_id=" . $categoryId . "' 
                                AND language_id='" . $languageId . "' ) as keyword"
            );
        } else {
            $arSelect[] = $db->raw(
                "(SELECT COUNT(p2c.product_id) as cnt
                      FROM " . $db->table_name('products_to_categories') . " p2c
                      INNER JOIN " . $db->table_name('products') . " p 
                         ON p.product_id = p2c.product_id AND p.status = '1'
                      WHERE  p2c.category_id = " . $db->table_name('categories') . ".category_id
                     ) as products_count"
            );
        }
        /** @var Collection|QueryBuilder $query */
        $query = self::select($arSelect);
        $query->leftJoin(
            'category_descriptions',
            function ($join) use ($languageId) {
                /** @var JoinClause $join */
                $join->on('category_descriptions.category_id', '=', 'categories.category_id')
                    ->where('category_descriptions.language_id', '=', $languageId);
            }
        )
            ->leftJoin(
                'categories_to_stores',
                'categories_to_stores.category_id',
                '=',
                'categories.category_id'
            )
            ->where('categories.category_id', '=', $categoryId)
            ->where('categories_to_stores.store_id', '=', $storeId);
        //allow to extend this method from extensions
        Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $query, func_get_args());
        return $query->first()?->toArray();
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
            $query->active('categories')
                ->where('categories.active_products_count', '>', 0);
        }
        $query->orderBy('sort_order');

        //allow to extend this method from extensions
        Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $query, func_get_args());
        $categories = $query->useCache('category')->get();
        $output = [];
        foreach ($categories as $category) {
            $output[] = $category->category_id;
            $output = array_merge(
                $output,
                static::getChildrenIDs($category->category_id, $mode)
            );
        }

        return $output;
    }

    /**
     * @param int|null $storeId
     * @return array
     * @throws AException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public static function getAllCategories(?int $storeId = 0)
    {
        return static::getCategories(-1, $storeId);
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

        //allow to extend this method from extensions
        Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $query, func_get_args());
        return $query->useCache('category')->get()->count();
    }

    /**
     * @param $params
     *
     * @return Collection
     * @throws ReflectionException
     * @throws AException
     * @throws InvalidArgumentException
     */
    public static function getCategoriesData($params)
    {

        $language_id = $params['language_id'] = ($params['language_id'] ?: static::$current_language_id);
        $params['sort'] = $params['sort'] ?: 'name';
        $params['sort'] = $params['sort'] == 'keyword' ? 'name' : $params['sort'];

        $params['order'] = $params['order'] ?: 'ASC';
        $params['start'] = max($params['start'], 0);
        $params['limit'] = isset($params['limit']) ? abs((int)$params['limit']) : null;
        $filter = (array)$params['filter'];

        $store_id = (int)$filter['store_id'] ?: (int)Registry::config()->get('config_store_id');

        $db = Registry::db();

        $arSelect = [];
        if (ABC::env('IS_ADMIN')) {
            $arSelect[] = 'category_descriptions.name as basename';
        }
        $query = self::selectRaw(Registry::db()->raw_sql_row_count() . ' ' . $db->table_name('categories') . '.*')
            ->addSelect($arSelect)
            ->leftJoin(
                'category_descriptions',
                function ($join) use ($language_id) {
                    /** @var JoinClause $join */
                    $join->on('category_descriptions.category_id', '=', 'categories.category_id')
                        ->where('category_descriptions.language_id', '=', $language_id);
                }
            )
            ->join(
                'categories_to_stores',
                function ($join) use ($store_id) {
                    /** @var JoinClause $join */
                    $join->on('categories_to_stores.category_id', '=', 'categories.category_id')
                        ->where('categories_to_stores.store_id', '=', $store_id);
                }
            );

        if (isset($filter['parent_id'])) {
            if ($filter['parent_id'] > 0) {
                $query->where('categories.parent_id', '=', $filter['parent_id']);
            } else {
                $query->whereNull('categories.parent_id');
            }
        }


        if (isset($filter['status'])) {
            $query->where('categories.status', '=', (int)$filter['status']);
        }
        //include ids set
        if (isset($filter['include'])) {
            $filter['include'] = array_map('intval', (array)$filter['include']);
            $query->whereIn('categories.category_id', $filter['include']);
        }
        //exclude already selected in chosen element
        if (isset($filter['exclude'])) {
            $filter['exclude'] = array_map('intval', (array)$filter['exclude']);
            $query->whereNotIn('categories.category_id', $filter['exclude']);
        }

        if (isset($filter['keyword'])) {
            $query->where(function ($query) use ($filter) {
                /** @var QueryBuilder $query */
                if ($filter['search_operator'] != 'like') {
                    $query->orWhere(
                        'category_descriptions.name',
                        '=',
                        mb_strtolower($filter['keyword'])
                    )
                        ->orWhere(
                            'category_descriptions.description',
                            '=',
                            mb_strtolower($filter['keyword'])
                        )
                        ->orWhere(
                            'category_descriptions.meta_keywords',
                            '=',
                            mb_strtolower($filter['keyword'])
                        );
                } else {
                    $query->orWhere(
                        'category_descriptions.name',
                        'like',
                        "%" . mb_strtolower($filter['keyword']) . "%"
                    );
                    $query->orWhere(
                        'category_descriptions.description',
                        'like',
                        "%" . mb_strtolower($filter['keyword']) . "%"
                    );
                    $query->orWhere(
                        'category_descriptions.meta_keywords',
                        'like',
                        "%" . mb_strtolower($filter['keyword']) . "%"
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

        if (isset($params['sort']) && in_array($params['sort'], array_keys($sort_data))) {
            $sortBy = $params['sort'];
        } else {
            $sortBy = 'categories.sort_order';
        }

        if (isset($params['order']) && (strtoupper($params['order']) == 'DESC')) {
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

        if (isset($params['limit'])) {
            if ($params['limit'] < 1) {
                $params['limit'] = 20;
            }

            $query->limit($params['limit'])
                ->offset($params['start']);
        }

        //allow to extend this method from extensions
        Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $query, $params);
        $items = $query->useCache('category')->get();
        foreach ($items as &$item) {
            if ($params['basename']) {
                $item->name = $item->basename;
            } else {
                $item->name = static::getPath($item->category_id, 'name');
            }
        }
        return $items;
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
            $db->table('categories_to_stores')
                ->where('category_id', '=', (int)$categoryId)
                ->delete();
            if (isset($data['category_store'])) {
                foreach ($data['category_store'] as $store_id) {
                    $categoryToStore[] = [
                        'category_id' => $categoryId,
                        'store_id'    => (int)$store_id,
                    ];
                }
            } else {
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
            //allow to extend this method from extensions
            Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $category, func_get_args());
            UrlAlias::setCurrentLanguageID(static::$current_language_id);
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
            Registry::log()->error($e->getMessage());
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
        $cache = Registry::cache();
        $db->beginTransaction();

        try {
            if (isset($data['parent_id'])) {
                $data['parent_id'] = (int)$data['parent_id'] > 0 ? (int)$data['parent_id'] : null;
            }

            $category = self::find($categoryId);
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
            $db->table('categories_to_stores')
                ->where('category_id', '=', (int)$categoryId)
                ->delete();
            if (isset($data['category_store'])) {
                foreach ($data['category_store'] as $store_id) {
                    $categoryToStore[] = [
                        'category_id' => $categoryId,
                        'store_id'    => (int)$store_id,
                    ];
                }
            } else {
                $categoryToStore[] = [
                    'category_id' => $categoryId,
                    'store_id'    => 0,
                ];
            }
            $db->table('categories_to_stores')
                ->insert($categoryToStore);

            if ($data['keywords']) {
                UrlAlias::replaceKeywords($data['keywords'], $category->getKeyName(), $category->getKey());
            } elseif (isset($data['keyword'])) {
                UrlAlias::setCategoryKeyword((string)$data['keyword'], (int)$categoryId);
            }

            //allow to extend this method from extensions
            Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $category, func_get_args());

            $cache->flush('category');
            $cache->flush('product');
            $db->commit();
            //call event listener on saved
            $category->touch();
            return true;
        } catch (Exception|Error $e) {
            $db->rollback();
            Registry::log()->error(__CLASS__ . " " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * @param $categoryId
     *
     * @return bool
     * @throws InvalidArgumentException
     */
    public static function deleteCategory($categoryId)
    {
        $category = self::find((int)$categoryId);
        $cache = Registry::cache();

        if (!$category) {
            throw new Exception('Cannot to find category ID ' . $categoryId);
        }

        //run recalculation of products count before delete
        //(in case with data inconsistency)
        $category->touch();
        $category->refresh();

        //do not allow non-empty category
        if ($category->total_products_count) {
            throw new Exception(
                'Cannot to delete category ID ' . $categoryId . '. It have ' . $category->total_products_count . ' products!'
            );
        }

        //get all children of category by tree and add current
        $subCategories = self::getChildrenIDs((int)$categoryId);
        $subCategories[] = $categoryId;

        foreach ($subCategories as $categoryId) {
            UrlAlias::where(
                'query',
                '=',
                'category_id=' . (int)$categoryId
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
                //allow to extend this method from extensions
                Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $category, func_get_args());
                $category->forceDelete();
            }
            if ($parentId) {
                $parent = Category::find($parentId);
                $parent?->touch();
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
        $query = self::select(['categories.category_id']);
        $query->leftJoin(
            'categories as t2',
            't2.parent_id',
            '=',
            'categories.category_id'
        )->whereNull('t2.category_id');

        $categories = $query->useCache('category')->get();

        $result = [];
        if ($categories) {
            return array_column((array)$categories?->toArray(), 'category_id', 'category_id');
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
        $categoryDescriptions = CategoryDescription::where('category_id', '=', (int)$category_id)->get();

        if (!$categoryDescriptions) {
            return [];
        }

        $categoryDescriptions = $categoryDescriptions->toArray();
        $category_description_data = [];
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
            ->useCache('category')
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

        $query = static::select(["category_descriptions", 'categories.*']);
        $query->selectRaw(
            "(SELECT keyword
                            FROM " . Registry::db()->table_name("url_aliases") . " 
                            WHERE query = 'category_id=" . $category_id . "'
                            AND language_id='" . $language_id . "' ) as keyword"
        );
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

        //allow to extend this method from extensions
        Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $query, func_get_args());
        return $query->useCache('category')->get()?->toArray();
    }

    /**
     * @param string $name
     * @param int|null $parent_id
     *
     * @return Model|object|QueryBuilder|null
     */
    public static function getCategoryByName(string $name, $parent_id = null)
    {
        $db = Registry::db();
        $name = $db->escape(mb_strtolower(html_entity_decode($name, ENT_QUOTES, ABC::env('APP_CHARSET'))));
        /** @var QueryBuilder $query */
        $query = CategoryDescription::whereRaw("LOWER(name) = '" . $name . "'");
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

        //allow to extend this method from extensions
        Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $query, func_get_args());
        return $query->first();
    }

    public static function getTotalActiveProductsByCategoryId($categoryId = 0, $storeId = 0)
    {
        $query = Category::where('category_id', '=', $categoryId);
        //allow to extend this method from extensions
        Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $query, func_get_args());
        return $query->first()?->active_products_count;
    }
}