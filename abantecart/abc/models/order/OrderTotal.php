<?php

namespace abc\models\order;

use abc\models\BaseModel;

/**
 * Class OrderTotal
 *
 * @property int $order_total_id
 * @property int $order_id
 * @property string $title
 * @property string $text
 * @property float $value
 * @property int $sort_order
 * @property string $type
 * @property string $key
 *
 * @property Order $order
 *
 * @package abc\models
 */
class OrderTotal extends BaseModel
{
    protected $primaryKey = 'order_total_id';
    public $timestamps = false;

    protected $casts = [
        'order_id'   => 'int',
        'value'      => 'float',
        'sort_order' => 'int',
    ];

    protected $fillable = [
        'order_id',
        'title',
        'text',
        'value',
        'sort_order',
        'type',
        'key',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
