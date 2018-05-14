<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class AcProductOptionValue
 *
 * @property int                                      $product_option_value_id
 * @property int                                      $product_option_id
 * @property int                                      $product_id
 * @property int                                      $group_id
 * @property string                                   $sku
 * @property int                                      $quantity
 * @property int                                      $subtract
 * @property float                                    $price
 * @property string                                   $prefix
 * @property float                                    $weight
 * @property string                                   $weight_type
 * @property int                                      $attribute_value_id
 * @property string                                   $grouped_attribute_data
 * @property int                                      $sort_order
 * @property int                                      $default
 *
 * @property \abc\models\AcProductOption              $product_option
 * @property \abc\models\Product                      $product
 * @property \Illuminate\Database\Eloquent\Collection $order_options
 *
 * @package abc\models
 */
class ProductOptionValue extends AModelBase
{
    protected $primaryKey = 'product_option_value_id';
    public $timestamps = false;

    protected $casts = [
        'product_option_id'  => 'int',
        'product_id'         => 'int',
        'group_id'           => 'int',
        'quantity'           => 'int',
        'subtract'           => 'int',
        'price'              => 'float',
        'weight'             => 'float',
        'attribute_value_id' => 'int',
        'sort_order'         => 'int',
        'default'            => 'int',
    ];

    protected $fillable = [
        'product_option_id',
        'product_id',
        'group_id',
        'sku',
        'quantity',
        'subtract',
        'price',
        'prefix',
        'weight',
        'weight_type',
        'attribute_value_id',
        'grouped_attribute_data',
        'sort_order',
        'default',
    ];

    public function product_option()
    {
        return $this->belongsTo(ProductOption::class, 'product_option_id');
    }

    public function product()
    {
        return $this->belongsTo(\abc\models\Product::class, 'product_id');
    }

    public function order_options()
    {
        return $this->hasMany(OrderOption::class, 'product_option_value_id');
    }
}
