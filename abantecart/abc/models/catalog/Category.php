<?php

namespace abc\models\catalog;

use abc\core\ABC;
use abc\models\BaseModel;
use abc\models\system\Store;
use H;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletes;

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
 */
class Category extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = [
        'descriptions',
        'products',
        'stores',
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
        'parent_id',
        'sort_order',
        'status',
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

    public function getPath($category_id, $mode = '')
    {
        $category_id = (int)$category_id;

        $categories = $this->db->table('categories as c')
            ->leftJoin('category_descriptions as cd', 'c.category_id', '=', 'cd.category_id')
            ->where('c.category_id', '=', (int)$category_id)
            ->where('cd.language_id', '=', $this->registry->get('language')->getContentLanguageID())
            ->orderBy('c.sort_order')
            ->orderBy('cd.name')
            ->get()
            ->toArray();

        $category_info = current($categories);

        if ($category_info->parent_id) {
            if ($mode == 'id') {
                return $this->getPath($category_info->parent_id, $mode).'_'
                    .$category_info->category_id;
            } else {
                return $this->getPath($category_info->parent_id, $mode)
                    .$this->registry->get('language')->get('text_separator')
                    .$category_info->name;
            }
        } else {
            return $mode == 'id' ? $category_info->category_id : $category_info->name;
        }
    }

    /**
     * @param      $parent_id
     * @param null $store_id
     * @param int  $limit
     *
     * @return array
     */
    public function getCategories($parentId, $storeId = null, $limit = 0)
    {
        if (ABC::env('IS_ADMIN')) {
            $languageId = (int)$this->registry->get('language')->getContentLanguageID();
        } else {
            $languageId = (int)$this->config->get('storefront_language_id');
        }

        $cacheKey = 'category.list.'.$parentId.'.store_'.$storeId.'_limit_'.$limit.'_lang_'.$languageId;
        $cache = $this->cache->pull($cacheKey);

        if ($cache === false) {
            $category_data = [];

            //  $this->db->enableQueryLog();

            $categories = self::leftJoin('category_descriptions', 'categories.category_id', '=', 'category_descriptions.category_id');
            if (!is_null($storeId)) {
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

            //\H::df($this->db->getQueryLog());

            foreach ($categories as $category) {
                $name = $category->name;
                if (ABC::env('IS_ADMIN')) {
                    $name = $this->getPath($category->category_id);
                }
                $category_data[] = [
                    'category_id' => $category->category_id,
                    'parent_id'   => $category->parent_id,
                    'name'        => $name,
                    'status'      => $category->status,
                    'sort_order'  => $category->sort_order,
                ];
                $category_data = array_merge($category_data, $this->getCategories($category->category_id, $storeId));
            }
            $cache = $category_data;
            $this->cache->push($cacheKey, $cache);
        }

        return $cache;
    }

    /**
     * @param int $categoryId
     *
     * @return false|mixed
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     */
    public function getCategory(int $categoryId)
    {
        $storeId = (int)$this->config->get('config_store_id');
        if (ABC::env('IS_ADMIN')) {
            $languageId = (int)$this->registry->get('language')->getContentLanguageID();
        } else {
            $languageId = (int)$this->config->get('storefront_language_id');
        }

        $cacheKey = 'product.listing.category.'.(int)$categoryId.'.store_'.$storeId.'_lang_'.$languageId;
        $cache = $this->cache->pull($cacheKey);
        if ($cache === false) {

            $arSelect = ['*'];

            if (ABC::env('IS_ADMIN')) {
                $arSelect[] = $this->db->raw("(SELECT keyword FROM ".$this->db->table_name("url_aliases")
                    ." WHERE query = 'category_id=".$categoryId."' AND language_id='".$languageId."' ) as keyword");
            } else {
                $arSelect[] = $this->db->raw("(SELECT COUNT(p2c.product_id) as cnt
										 FROM ".$this->db->table_name('products_to_categories')." p2c
										 INNER JOIN ".$this->db->table_name('products')." p ON p.product_id = p2c.product_id AND p.status = '1'
										 WHERE  p2c.category_id = ".$this->db->table_name('categories').".category_id) as products_count");
            }

            $category = self::select($arSelect);

            $category = $category->leftJoin('category_descriptions', function ($join) use ($languageId) {
                $join->on('category_descriptions.category_id', '=', 'categories.category_id')
                    ->where('category_descriptions.language_id', '=', $languageId);
            })
                ->leftJoin('categories_to_stores', 'categories_to_stores.category_id', '=', 'categories.category_id')
                ->where('categories.category_id', '=', $categoryId)
                ->where('categories_to_stores.store_id', '=', $storeId)
                ->active('categories')
                ->first();

            if ($category) {
                $cache = $category->toArray();
                $this->cache->push($cacheKey, $cache);
            }
        }
        return $cache;
    }

    /**
     * @param int $parent_id
     *
     * @return array
     */
    public function getChildrenIDs($parentId)
    {
        $languageId = (int)$this->config->get('storefront_language_id');
        $storeId = (int)$this->config->get('config_store_id');
        $cacheKey = 'category.list.'.$parentId.'.store_'.$storeId.'_lang_'.$languageId;
        $cache = $this->cache->pull($cacheKey);

        if ($cache === false) {
            $categories = self::select(['categories.category_id'])
                ->leftJoin('categories_to_stores', 'categories_to_stores.category_id', '=', 'categories.category_id');

            if ($parentId >= 0) {
                $categories = $categories->where('categories.parent_id', '=', $parentId);
            }
            $categories = $categories->where('categories_to_stores.store_id', '=', $storeId)
                ->active('categories')
                ->get();

            $cache = array();
            foreach ($categories as $category) {
                $cache[] = $category->category_id;
                $cache = array_merge($cache, $this->getChildrenIDs($category->category_id));
            }
            $this->cache->push($cacheKey, $cache);
        }
        return $cache;
    }

    /**
     * @return array
     */
    public function getAllCategories()
    {
        return $this->getCategories(-1);
    }

    /**
     * @param int $parent_id
     *
     * @return int
     */
    public function getTotalCategoriesByCategoryId($parentId = null)
    {
        $categoriesCount = 0;
        $categories = self::select(['categories.category_id'])
            ->leftJoin('categories_to_stores', 'categories_to_stores.category_id', '=', 'categories.category_id')
            ->where('categories_to_stores.store_id', '=', (int)$this->config->get('config_store_id'))
            ->active('categories');
        if ($parentId) {
            $categories = $categories->where('categories.parent_id', '=', $parentId);
        } else {
            $categories = $categories->whereNull('categories.parent_id');
        }
        $categoriesCount = $categories->get()->count();
        return $categoriesCount;
    }

    /**
     * @param $category_id
     *
     * @return string
     * @throws \Exception
     */
    public function buildPath(int $category_id)
    {
        $categories = self::find($category_id)
            ->first(['category_id', 'parent_id']);

        if ($categories && $categories->parent_id) {
            return $this->buildPath($categories->parent_id)."_".$categories->category_id;
        } else {
            return $category_id;
        }
    }

    public function getCategoriesData($data, $mode = 'default')
    {

        if ($data['language_id']) {
            $language_id = (int)$data['language_id'];
        } else {
            $language_id = (int)$this->config->get('storefront_language_id');
        }

        if ($data['store_id']) {
            $store_id = (int)$data['store_id'];
        } else {
            $store_id = (int)$this->config->get('config_store_id');
        }

        if ($mode == 'total_only') {
            $total_sql = 'count(*) as total';
        } else {
            $total_sql = "*,
						  c.category_id,
						  (SELECT count(*) as cnt
						  	FROM ".$this->db->table_name('products_to_categories')." p2c
						  	INNER JOIN ".$this->db->table_name('products')." p ON p.product_id = p2c.product_id
						  	WHERE p2c.category_id = c.category_id AND p.status = '1') as products_count ";
        }
        $where = (isset($data['parent_id']) ? " c.parent_id = '".(int)$data['parent_id']."'" : '');
        //filter result by given ids array
        if ($data['filter_ids']) {
            $ids = array();
            foreach ($data['filter_ids'] as $id) {
                $id = (int)$id;
                if ($id) {
                    $ids[] = $id;
                }
            }
            $where = " c.category_id IN (".implode(', ', $ids).")";
        }

        $where = $where ? 'WHERE '.$where : '';

        $sql = "SELECT ".$total_sql."
				FROM ".$this->db->table_name('categories')." c
				LEFT JOIN ".$this->db->table_name('category_descriptions')." cd
					ON (c.category_id = cd.category_id AND cd.language_id = '".$language_id."')
				INNER JOIN ".$this->db->table_name('categories_to_stores')." cs
					ON (c.category_id = cs.category_id AND cs.store_id = '".$store_id."')
				".$where;

        if (!empty($data['subsql_filter'])) {
            $sql .= ($where ? " AND " : 'WHERE ').$data['subsql_filter'];
        }

        //If for total, we done building the query
        if ($mode == 'total_only') {
            $query = $this->db->query($sql);
            return $query->row['total'];
        }

        $sort_data = array(
            'name'       => 'cd.name',
            'status'     => 'c.status',
            'sort_order' => 'c.sort_order',
        );

        if (isset($data['sort']) && in_array($data['sort'], array_keys($sort_data))) {
            $sql .= " ORDER BY ".$data['sort'];
        } else {
            $sql .= " ORDER BY c.sort_order, cd.name ";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT ".(int)$data['start'].",".(int)$data['limit'];
        }

        $query = $this->db->query($sql);
        $category_data = array();
        foreach ($query->rows as $result) {
            $category_data[] = array(
                'category_id'    => $result['category_id'],
                'name'           => $result['name'],
                'status'         => $result['status'],
                'sort_order'     => $result['sort_order'],
                'products_count' => $result['products_count'],

            );
        }
        return $category_data;
    }

    public function addCategory($data)
    {
        $data['parent_id'] = (int)$data['parent_id'] > 0 ? (int)$data['parent_id'] : null;
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
                    'name'             => $value['name'],
                    'meta_keywords'    => $value['meta_keywords'],
                    'meta_description' => $value['meta_description'],
                    'description'      => $value['description'],
                ];
                $description = new CategoryDescription($arDescription);
                $category->descriptions()->save($description);
            }
        }

        if (isset($data['category_store'])) {
            $categoryToStore = [];
            foreach ($data['category_store'] as $store_id) {
                $categoryToStore[] = [
                    'category_id' => $categoryId,
                    'store_id'    => (int)$store_id,
                ];
            }
            $this->db->table('categories_to_stores')->insert($categoryToStore);
        }

        if ($data['keyword']) {
            $seo_key = H::SEOEncode($data['keyword'], 'category_id', $categoryId);
        } else {
            //Default behavior to save SEO URL keyword from category name in default language
            $seo_key = H::SEOEncode($data['category_description'][$this->language->getDefaultLanguageID()]['name'],
                'category_id',
                $categoryId);
        }
        if ($seo_key) {
            $this->registry->get('language')->replaceDescriptions('url_aliases',
                ['query' => "category_id=".(int)$categoryId],
                [(int)$this->registry->get('language')->getContentLanguageID() => ['keyword' => $seo_key]]);
        } else {
            UrlAlias::where('query', '=', 'category_id='.(int)$categoryId)
                ->where('language_id', '=', (int)$this->registry->get('language')->getContentLanguageID())
                ->forceDelete();
        }

        $this->cache->remove('category');

        return $categoryId;
    }

    public function editCategory($categoryId, $data)
    {
        $data['parent_id'] = (int)$data['parent_id'] > 0 ? "'".(int)$data['parent_id']."'" : null;
        $contentLanguageId = $this->registry->get('language')->getContentLanguageID();

        self::find($categoryId)->update($data);

        if (!empty($data['category_description'])) {
            foreach ($data['category_description'] as $language_id => $value) {
                $update = [];
                if (isset($value['name'])) {
                    $update['name'] = $value['name'];
                }
                if (isset($value['description'])) {
                    $update['description'] = $value['description'];
                }
                if (isset($value['meta_keywords'])) {
                    $update['meta_keywords'] = $value['meta_keywords'];
                }
                if (isset($value['meta_description'])) {
                    $update['meta_description'] = $value['meta_description'];
                }
                if (!empty($update)) {
                    // insert or update
                    $this->registry->get('language')->replaceDescriptions('category_descriptions',
                        ['category_id' => (int)$categoryId],
                        [$language_id => $update]);
                }
            }
        }

        if (isset($data['category_store'])) {
            $this->db->table('categories_to_stores')
                ->where('category_id', '=', $categoryId)
                ->delete();

            $categoryToStore = [];
            foreach ($data['category_store'] as $storeId) {
                $categoryToStore[] = [
                    'category_id' => (int)$categoryId,
                    'store_id' => (int)$storeId,
                ];
            }
            $this->db->table('categories_to_stores')->insert($categoryToStore);
        }

        if (isset($data['keyword'])) {
            $data['keyword'] = H::SEOEncode($data['keyword']);
            if ($data['keyword']) {
                $this->registry->get('language')->replaceDescriptions('url_aliases',
                    ['query' => "category_id=".(int)$categoryId],
                    [$contentLanguageId => ['keyword' => $data['keyword']]]
                );
            } else {
                UrlAlias::where('query', '=', 'category_id='.(int)$categoryId)
                    ->where('language_id', '=', $contentLanguageId)
                    ->forceDelete();
            }
        }

        $this->cache->remove('category');
        $this->cache->remove('product');

    }
}
