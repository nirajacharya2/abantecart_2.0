<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class AcProductsToStore
 *
 * @property int $product_id
 * @property int $store_id
 *
 * @property \abc\models\Product $product
 * @property \abc\models\Store $store
 *
 * @package abc\models
 */
class ProductsToStore extends AModelBase
{
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'product_id' => 'int',
        'store_id'   => 'int',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
}
