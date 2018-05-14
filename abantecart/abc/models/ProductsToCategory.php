<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class AcProductsToCategory
 *
 * @property int                    $product_id
 * @property int                    $category_id
 *
 * @property \abc\models\Product    $product
 * @property \abc\models\AcCategory $category
 *
 * @package abc\models
 */
class ProductsToCategory extends AModelBase
{
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'product_id'  => 'int',
        'category_id' => 'int',
    ];

    public function product()
    {
        return $this->belongsTo(\abc\models\Product::class, 'product_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
