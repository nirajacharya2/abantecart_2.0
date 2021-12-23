<?php

namespace abc\models\order;

use abc\models\BaseModel;
use abc\modules\events\ABaseEvent;
use H;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class OrderHistory
 *
 * @property int $order_history_id
 * @property int $order_id
 * @property int $order_status_id
 * @property int $notify
 * @property string $comment
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @property OrderStatus $order_status
 *
 * @package abc\models
 */
class OrderHistory extends BaseModel
{
    use SoftDeletes;

    protected $table = 'order_history';
    protected $primaryKey = 'order_history_id';
    protected $mainClassName = Order::class;
    protected $mainClassKey = 'order_id';

    protected $casts = [
        'order_id'        => 'int',
        'order_status_id' => 'int',
        'notify'          => 'int',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'order_id',
        'order_status_id',
        'notify',
        'comment',
    ];

    protected $rules = [
        /** @see validate() */
        'order_id'        => [
            'checks'   => [
                'integer',
                'required',
                'exists:orders',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer or not presents in orders table!',
                ],
            ],
        ],
        'order_status_id' => [
            'checks'   => [
                'integer',
                'required',
                'exists:order_statuses',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer or absent in order_statuses table!',
                ],
            ],
        ],
        'notify'          => [
            'checks'   => [
                'boolean',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be an integer!',
                ],
            ],
        ],
        'comment'         => [
            'checks'   => [
                'string',
                'max:1500',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
    ];

    public function SetCommentAttribute($value)
    {
        $this->attributes['comment'] = strip_tags((string)$value);
    }

    public function order_status()
    {
        return $this->belongsTo(OrderStatus::class, 'order_status_id');
    }

    public function order_status_description()
    {
        return $this->hasOne(OrderStatusDescription::class, 'order_status_id', 'order_status_id')
                    ->where('language_id', '=', static::$current_language_id);
    }

    public function save(array $options = [])
    {
        parent::save($options);
        //touch orders table
        $order = Order::find($this->order_id);
        $order->update(['order_status_id' => $this->order_status_id]);
    }
}
