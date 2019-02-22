<?php

namespace abc\models\catalog;

use abc\models\BaseModel;
use abc\models\system\Store;
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
    const DELETED_AT = 'date_deleted';
    protected $cascadeDeletes = [
        'descriptions',
        'products',
        'stores'
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

    public function getCategories($parent_id, $store_id = null)
    {
        $language_id = $this->registry->get('language')->getContentLanguageID();

        $category_data = [];

        $categories = $this->db->table('categories as c')
            ->leftJoin('category_descriptions as cd', 'c.category_id', '=', 'cd.category_id');
        if (!is_null($store_id)) {
            $categories = $categories->rightJoin('categories_to_stores as cs', function ($join) use ($store_id) {
                $join->on('c.category_id', '=', 'cs.category_id')
                     ->where('cs.store_id', '=', (int)$store_id);
            });
        }

        $categories = $categories->whereRaw('COALESCE(parent_id,0) = '.(int)$parent_id)
                                 ->where('cd.language_id', '=', (int)$language_id)
                                 ->orderBy('c.sort_order')
                                 ->orderBy('cd.name')
                                 ->get()
                                 ->toArray();

        foreach ($categories as $category) {
            $category_data[] = [
                'category_id' => $category->category_id,
                'parent_id'   => $category->parent_id,
                'name'        => $this->getPath($category->category_id),
                'status'      => $category->status,
                'sort_order'  => $category->sort_order,
            ];
            $category_data = array_merge($category_data, $this->getCategories($category->category_id, $store_id));
        }

        return $category_data;
    }
}
