<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class OrderProduct
 *
 * @property int $order_product_id
 * @property int $order_id
 * @property int $product_id
 * @property string $name
 * @property string $model
 * @property string $sku
 * @property float $price
 * @property float $total
 * @property float $tax
 * @property int $quantity
 * @property int $subtract
 *
 * @property \abc\models\Order $order
 * @property \abc\models\Product $product
 * @property \Illuminate\Database\Eloquent\Collection $order_downloads
 * @property \Illuminate\Database\Eloquent\Collection $order_downloads_histories
 *
 * @package abc\models
 */
class OrderProduct extends AModelBase
{
    protected $primaryKey = 'order_product_id';
    public $timestamps = false;

    protected $casts = [
        'order_id'   => 'int',
        'product_id' => 'int',
        'price'      => 'float',
        'total'      => 'float',
        'tax'        => 'float',
        'quantity'   => 'int',
        'subtract'   => 'int',
    ];

    protected $fillable = [
        'order_id',
        'product_id',
        'name',
        'model',
        'sku',
        'price',
        'total',
        'tax',
        'quantity',
        'subtract',
    ];

    public function order()
    {
        return $this->belongsTo(\abc\models\Order::class, 'order_id');
    }

    public function product()
    {
        return $this->belongsTo(\abc\models\Product::class, 'product_id');
    }

    public function order_downloads()
    {
        return $this->hasMany(OrderDownload::class, 'order_product_id');
    }

    public function order_downloads_histories()
    {
        return $this->hasMany(OrderDownloadsHistory::class, 'order_product_id');
    }
}
