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
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class OrderDatum
 *
 * @property int $order_id
 * @property int $type_id
 * @property string $data
 * @property Carbon $date_added
 * @property Carbon $date_modified
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
        'order_id'      => 'int',
        'type_id'       => 'int',
        'data'          => Serialized::class,
        'date_added'    => 'datetime',
        'date_modified' => 'datetime'
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

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function order_data_type()
    {
        return $this->belongsTo(OrderDataType::class, 'type_id');
    }
}