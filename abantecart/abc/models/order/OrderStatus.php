<?php

namespace abc\models\order;

use abc\models\BaseModel;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    use SoftDeletes, CascadeSoftDeletes;
    const DELETED_AT = 'date_deleted';
    protected $cascadeDeletes = ['descriptions'];

    public $timestamps = false;


    public function descriptions()
    {
        return $this->hasMany(OrderStatusDescription::class, 'order_status_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'order_status_id');
    }
}
