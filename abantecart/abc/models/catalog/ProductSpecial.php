<?php

namespace abc\models\catalog;

use abc\models\BaseModel;

/**
 * Class ProductSpecial
 *
 * @property int $product_special_id
 * @property int $product_id
 * @property int $customer_group_id
 * @property int $priority
 * @property float $price
 * @property \Carbon\Carbon $date_start
 * @property \Carbon\Carbon $date_end
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @property Product $product
 *
 * @package abc\models
 */
class ProductSpecial extends BaseModel
{
    protected $primaryKey = 'product_special_id';
    public $timestamps = false;

    protected $casts = [
        'product_id'        => 'int',
        'customer_group_id' => 'int',
        'priority'          => 'int',
        'price'             => 'float',
    ];

    protected $dates = [
        'date_start',
        'date_end',
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'product_id',
        'customer_group_id',
        'priority',
        'price',
        'date_start',
        'date_end',
        'date_added',
        'date_modified',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
