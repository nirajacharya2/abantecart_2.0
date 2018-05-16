<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class AcCategory
 *
 * @property int $category_id
 * @property int $parent_id
 * @property int $sort_order
 * @property int $status
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @property \Illuminate\Database\Eloquent\Collection $categories_to_stores
 * @property \Illuminate\Database\Eloquent\Collection $category_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $products_to_categories
 *
 * @package abc\models
 */
class Category extends AModelBase
{
    protected $primaryKey = 'category_id';
    public $timestamps = false;

    protected $casts = [
        'parent_id'  => 'int',
        'sort_order' => 'int',
        'status'     => 'int',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'parent_id',
        'sort_order',
        'status',
        'date_added',
        'date_modified',
    ];

    public function categories_to_stores()
    {
        return $this->hasMany(CategoriesToStore::class, 'category_id');
    }

    public function category_descriptions()
    {
        return $this->hasMany(CategoryDescription::class, 'category_id');
    }

    public function products_to_categories()
    {
        return $this->hasMany(ProductsToCategory::class, 'category_id');
    }
}
