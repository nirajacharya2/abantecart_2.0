<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class ProductsRelated
 *
 * @property int $product_id
 * @property int $related_id
 *
 * @property \abc\models\base\Product $product
 *
 * @package abc\models
 */
class ProductsRelated extends AModelBase
{
    protected $table = 'products_related';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'product_id' => 'int',
        'related_id' => 'int',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
