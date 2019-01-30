<?php

namespace abc\models\catalog;

use abc\models\BaseModel;

/**
 * Class ProductDiscount
 *
 * @property int $product_discount_id
 * @property int $product_id
 * @property int $customer_group_id
 * @property int $quantity
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
class ProductDiscount extends BaseModel
{
    protected $primaryKey = 'product_discount_id';
    public $timestamps = false;

    protected $casts = [
        'product_id'        => 'int',
        'customer_group_id' => 'int',
        'quantity'          => 'int',
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
        'quantity',
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
