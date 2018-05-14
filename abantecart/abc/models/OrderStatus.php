<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class AcOrderStatus
 *
 * @property int                                      $order_status_id
 * @property string                                   $status_text_id
 *
 * @property \Illuminate\Database\Eloquent\Collection $order_histories
 * @property \Illuminate\Database\Eloquent\Collection $order_status_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $orders
 *
 * @package abc\models
 */
class OrderStatus extends AModelBase
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
        return $this->hasMany(\abc\models\Order::class, 'order_status_id');
    }
}
