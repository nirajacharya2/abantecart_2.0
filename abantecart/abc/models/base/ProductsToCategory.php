<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class ProductsToCategory
 *
 * @property int $product_id
 * @property int $category_id
 *
 * @property Product $product
 * @property Category $category
 *
 * @package abc\models
 */
class ProductsToCategory extends AModelBase
{
    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;

    protected $primaryKeySet = [
        'product_id',
        'category_id'
    ];

    protected $casts = [
        'product_id'  => 'int',
        'category_id' => 'int',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
