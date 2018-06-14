<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class ProductsToStore
 *
 * @property int $product_id
 * @property int $store_id
 *
 * @property Product $product
 * @property Store $store
 *
 * @package abc\models
 */
class ProductsToStore extends AModelBase
{
    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;

    protected $primaryKeySet = [
        'product_id',
        'store_id'
    ];

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
