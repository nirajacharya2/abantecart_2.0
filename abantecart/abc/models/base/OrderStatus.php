<?php

namespace abc\models\base;

use abc\models\BaseModel;

/**
 * Class OrderStatus
 *
 * @property int $order_status_id
 * @property string $status_text_id
 *
 * @property \Illuminate\Database\Eloquent\Collection $order_histories
 * @property \Illuminate\Database\Eloquent\Collection $order_status_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $orders
 *
 * @package abc\models
 */
class OrderStatus extends BaseModel
{
    public $timestamps = false;

    public function order_histories()
    {
        return $this->hasMany(OrderHistory::class, 'order_status_id');
    }

    public function order_status_descriptions()
    {
        return $this->hasMany(OrderStatusDescription::class, 'order_status_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'order_status_id');
    }
}
