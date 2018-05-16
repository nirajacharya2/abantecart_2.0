<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class ProductsFeatured
 *
 * @property int $product_id
 *
 * @property \abc\models\base\Product $product
 *
 * @package abc\models
 */
class ProductsFeatured extends AModelBase
{
    protected $table = 'products_featured';
    protected $primaryKey = 'product_id';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'product_id' => 'int',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
