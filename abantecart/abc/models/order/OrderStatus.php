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

    protected $primaryKey = 'order_status_id';
    protected $cascadeDeletes = ['descriptions'];

    public $timestamps = false;
    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $casts = [];
    protected $fillable = ['status_text_id'];

    protected $rules = [

        'status_text_id' => [
            'checks'   => [
                'string',
                'max:64',
                'required',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
    ];

    public function descriptions()
    {
        return $this->hasMany(OrderStatusDescription::class, 'order_status_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'order_status_id');
    }
}
