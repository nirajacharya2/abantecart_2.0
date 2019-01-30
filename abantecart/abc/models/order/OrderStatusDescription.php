<?php

namespace abc\models\order;

use abc\models\BaseModel;

/**
 * Class OrderStatusDescription
 *
 * @property int $order_status_id
 * @property int $language_id
 * @property string $name
 *
 * @property OrderStatus $order_status
 * @property Language $language
 *
 * @package abc\models
 */
class OrderStatusDescription extends BaseModel
{
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'order_status_id' => 'int',
        'language_id'     => 'int',
    ];

    protected $fillable = [
        'name',
    ];

    public function order_status()
    {
        return $this->belongsTo(OrderStatus::class, 'order_status_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
