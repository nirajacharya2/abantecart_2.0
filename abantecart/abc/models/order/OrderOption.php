<?php

namespace abc\models\order;

use abc\models\BaseModel;
use abc\models\catalog\ProductOptionValue;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletes;

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
class OrderOption extends BaseModel
{
    use SoftDeletes;

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

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function order_product()
    {
        return $this->belongsTo(OrderProduct::class, 'order_product_id');
    }

    public function product_option_value()
    {
        return $this->belongsTo(ProductOptionValue::class, 'product_option_value_id');
    }
}
