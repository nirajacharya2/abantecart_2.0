<?php

namespace abc\models\order;

use abc\models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class OrderDatum
 *
 * @property int $order_id
 * @property int $type_id
 * @property string $data
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @property Order $order
 * @property OrderDataType $order_data_type
 *
 * @package abc\models
 */
class OrderDatum extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'order_id',
        'type_id',
    ];

    protected $mainClassName = Order::class;
    protected $mainClassKey = 'order_id';

    protected $touches = ['order'];

    protected $casts = [
        'order_id' => 'int',
        'type_id'  => 'int',
        'data'     => 'serialized',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'type_id',
        'order_id',
        'data',
    ];

    protected $rules = [
        /** @see validate() */
        'type_id'  => [
            'checks'   => [
                'integer',
                'exists:order_data_types',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer or does not exists in the table "order_data_types"!',
                ],
            ],
        ],
        'order_id' => [
            'checks'   => [
                'integer',
                'required',
                'exists:orders',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer or does not exists in the table "orders"!',
                ],
            ],
        ],
    ];

    public function setDataAttribute($value)
    {
        $this->attributes['data'] = serialize($value);
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function order_data_type()
    {
        return $this->belongsTo(OrderDataType::class, 'type_id');
    }
}
