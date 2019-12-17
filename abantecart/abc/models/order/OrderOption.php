<?php

namespace abc\models\order;

use abc\models\BaseModel;
use abc\models\catalog\ProductOption;
use abc\models\catalog\ProductOptionValue;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class OrderOption
 *
 * @property int $order_option_id
 * @property int $order_id
 * @property int $order_product_id
 * @property int $product_option_value_id
 * @property string $name
 * @property string $sku
 * @property string $value
 * @property float $price
 * @property string $prefix
 * @property string $settings
 * @property float $weight
 * @property string $weight_type - "%" or 3 letter weight unit iso code
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @property ProductOptionValue $product_option_value
 *
 * @method static OrderOption find(int $order_option_id) OrderOption
 *
 * @package abc\models
 */
class OrderOption extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'order_option_id';
    protected $mainClassName = Order::class;
    protected $mainClassKey = 'order_id';
    protected $touches = ['order_product'];

    protected $casts = [
        'order_id'                => 'int',
        'order_product_id'        => 'int',
        'product_option_id'       => 'int',
        'product_option_value_id' => 'int',
        'price'                   => 'float',
        'settings'                => 'serialized',
        'weight'                  => 'float'
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'order_id',
        'order_product_id',
        'product_option_id',
        'product_option_value_id',
        'name',
        'sku',
        'value',
        'price',
        'prefix',
        'settings',
        'weight',
        'weight_type'
    ];

    protected $rules = [
        /** @see validate() */
        'order_id'                => [
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
        'order_product_id'        => [
            'checks'   => [
                'integer',
                'required',
                'exists:order_products',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer or not presents in order_products table!',
                ],
            ],
        ],
        'product_option_id'       => [
            'checks'   => [
                'integer',
                'nullable',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer!',
                ],
            ],
        ],
        'product_option_value_id' => [
            'checks'   => [
                'integer',
                'nullable',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer!',
                ],
            ],
        ],
        'name'                    => [
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
        'sku'                     => [
            'checks'   => [
                'string',
                'max:64',
                'nullable',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
        'value'                   => [
            'checks'   => [
                'string',
                'max:1500',
                'required',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
        'price'                   => [
            'checks'   => [
                'numeric',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be numeric!',
                ],
            ],
        ],

        'prefix' => [
            'checks'   => [
                'string',
                'size:1',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :size characters length!',
                ],
            ],
        ],
        'weight' => [
            'checks'   => [
                'numeric',
                'nullable'
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be numeric!',
                ],
            ],
        ],
        'weight_type' => [
            'checks'   => [
                'string',
                'max:3',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be maximum string 3 characters length!',
                ],
            ],
        ],
    ];

    public function setSettingsAttribute($value)
    {
        $this->attributes['settings'] = serialize($value);
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function order_product()
    {
        return $this->belongsTo(OrderProduct::class, 'order_product_id');
    }

    public function product_option()
    {
        return $this->belongsTo(ProductOption::class, 'product_option_id');
    }

    public function product_option_value()
    {
        return $this->belongsTo(ProductOptionValue::class, 'product_option_value_id');
    }
}
