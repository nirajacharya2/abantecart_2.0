<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class OrderOption
 *
 * @property int $order_option_id
 * @property int $order_id
 * @property int $order_product_id
 * @property int $product_option_value_id
 * @property string $name
 * @property string $sku
 * @property string $value
 * @property float $price
 * @property string $prefix
 * @property string $settings
 *
 * @property ProductOptionValue $product_option_value
 *
 * @package abc\models
 */
class OrderOption extends AModelBase
{
    protected $primaryKey = 'order_option_id';
    public $timestamps = false;

    protected $casts = [
        'order_id'                => 'int',
        'order_product_id'        => 'int',
        'product_option_value_id' => 'int',
        'price'                   => 'float',
    ];

    protected $fillable = [
        'order_id',
        'order_product_id',
        'product_option_value_id',
        'name',
        'sku',
        'value',
        'price',
        'prefix',
        'settings',
    ];

    public function product_option_value()
    {
        return $this->belongsTo(ProductOptionValue::class, 'product_option_value_id');
    }
}
