<?php

namespace abc\models\catalog;

use abc\models\BaseModel;

/**
 * Class ProductsFeatured
 *
 * @property int $product_id
 *
 * @property Product $product
 *
 * @package abc\models
 */
class ProductsFeatured extends BaseModel
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
