<?php

namespace abc\models\order;

use abc\models\BaseModel;
use abc\models\locale\Language;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    use SoftDeletes;

    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'order_status_id',
        'language_id',
    ];
    public $timestamps = false;
    protected $mainClassName = OrderStatus::class;
    protected $mainClassKey = 'order_status_id';
    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $casts = [
        'order_status_id' => 'int',
        'language_id'     => 'int',
    ];

    protected $fillable = [
        'language_id',
        'name',
    ];

    protected $rules = [

        'order_status_id' => [
            'checks'   => [
                'int',
                'exists:order_statuses',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer or absent in order_statuses table!',
                ],
            ],
        ],

        'language_id' => [
            'checks'   => [
                'int',
                'exists:languages',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer or absent in languages table!',
                ],
            ],
        ],

        'name' => [
            'checks'   => [
                'string',
                'max:32',
                'required',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
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
