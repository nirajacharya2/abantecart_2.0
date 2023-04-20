<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2022 Belavier Commerce LLC
 *
 * This source file is subject to Open Software License (OSL 3.0)
 * License details is bundled with this package in the file LICENSE.txt.
 * It is also available at this URL:
 * <http://www.opensource.org/licenses/OSL-3.0>
 *
 * UPGRADE NOTE:
 * Do not edit or add to this file if you wish to upgrade AbanteCart to newer
 * versions in the future. If you wish to customize AbanteCart for your
 * needs please refer to http://www.abantecart.com for more information.
 */
namespace abc\models\order;

use abc\models\BaseModel;
use abc\models\casts\Serialized;
use Carbon\Carbon;

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
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @property Order $order
 *
 * @method static OrderTotal find(int $order_total_id) OrderTotal
 *
 * @package abc\models
 */
class OrderTotal extends BaseModel
{
    protected $primaryKey = 'order_total_id';

    protected $mainClassName = Order::class;
    protected $mainClassKey = 'order_id';

    protected $touches = ['order'];

    protected $casts = [
        'order_id'      => 'int',
        'value'         => 'float',
        'data'          => Serialized::class,
        'sort_order'    => 'int',
        'date_added'    => 'datetime',
        'date_modified' => 'datetime'
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
