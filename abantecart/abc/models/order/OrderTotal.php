<?php

namespace abc\models\order;

use abc\models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

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
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @property Order $order
 *
 * @method static OrderTotal find(int $order_total_id) OrderTotal
 *
 * @package abc\models
 */
class OrderTotal extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'order_total_id';

    protected $mainClassName = Order::class;
    protected $mainClassKey = 'order_id';

    protected $touches = ['order'];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $casts = [
        'order_id'   => 'int',
        'value'      => 'float',
        'data'       => 'serialized',
        'sort_order' => 'int',
    ];

    protected $fillable = [
        'order_id',
        'title',
        'text',
        'value',
        'data',
        'sort_order',
        'type',
        'key',
    ];

    protected $rules = [

        'order_id' => [
            'checks'   => [
                'int',
                'exists:orders',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer or absent in orders table!',
                ],
            ],
        ],

        'title' => [
            'checks'   => [
                'string',
                'max:255',
                'required',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
        'text'  => [
            'checks'   => [
                'string',
                'max:255',
                'required',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],

        'value' => [
            'checks'   => [
                'numeric',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be numeric!',
                ],
            ],
        ],

        'sort_order' => [
            'checks'   => [
                'integer',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be an integer!',
                ],
            ],
        ],

        'type' => [
            'checks'   => [
                'string',
                'max:255',
                'required',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],

        'key' => [
            'checks'   => [
                'string',
                'max:128',
                'required',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
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
}
