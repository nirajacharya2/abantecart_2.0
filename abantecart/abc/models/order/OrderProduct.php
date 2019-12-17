<?php

namespace abc\models\order;

use abc\core\engine\Registry;
use abc\models\BaseModel;
use abc\models\catalog\Product;
use abc\models\QueryBuilder;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class OrderProduct
 *
 * @property int $order_product_id
 * @property int $order_id
 * @property int $product_id
 * @property int $order_status_id
 * @property string $name
 * @property string $model
 * @property string $sku
 * @property float $price
 * @property float $total
 * @property float $tax
 * @property int $quantity
 * @property int $subtract
 * @property int|null $tax_class_id
 * @property float|null $weight
 * @property int|null $weight_class_id
 * @property float|null $length
 * @property float|null $width
 * @property float|null $height
 * @property int|null $length_class_id
 * @property int $shipping
 * @property int $ship_individually
 * @property int $free_shipping
 * @property float|null $shipping_price
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @property Order $order
 * @property Product $product
 * @property OrderOption $order_options
 * @property OrderDownload $order_downloads
 * @property \Illuminate\Database\Eloquent\Collection $order_downloads_histories
 *
 * @method static OrderProduct find(int $order_product_id) OrderProduct
 * @method static OrderProduct select(mixed $select) Builder
 *
 * @package abc\models
 */
class OrderProduct extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['order_downloads'];

    protected $primaryKey = 'order_product_id';

    protected $touches = ['order'];

    protected $mainClassName = Order::class;
    protected $mainClassKey = 'order_id';

    protected $casts = [
        'order_id'          => 'int',
        'product_id'        => 'int',
        'price'             => 'float',
        'total'             => 'float',
        'tax'               => 'float',
        'quantity'          => 'int',
        'subtract'          => 'int',
        'order_status_id'   => 'int',
        'tax_class_id'      => 'int',
        'weight'            => 'float',
        'weight_class_id'   => 'int',
        'length'            => 'float',
        'width'             => 'float',
        'height'            => 'float',
        'length_class_id'   => 'int',
        'shipping'          => 'boolean',
        'ship_individually' => 'int',
        'free_shipping'     => 'boolean',
        'shipping_price'    => 'float',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'order_id',
        'product_id',
        'name',
        'model',
        'sku',
        'price',
        'total',
        'tax',
        'quantity',
        'subtract',
        'order_status_id',
        'tax_class_id',
        'weight',
        'weight_class_id',
        'length',
        'width',
        'height',
        'length_class_id',
        'shipping',
        'ship_individually',
        'free_shipping',
        'shipping_price'
    ];

    protected $rules = [
        /** @see validate() */
        'order_id'   => [
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
        'product_id' => [
            'checks'   => [
                'integer',
                'required',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer!',
                ],
            ],
        ],
        'name'  => [
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
        'model' => [
            'checks'   => [
                'string',
                'max:64',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
        'sku'   => [
            'checks'   => [
                'string',
                'max:64',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
        'price' => [
            'checks'   => [
                'numeric',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be numeric!',
                ],
            ],
        ],
        'total' => [
            'checks'   => [
                'numeric',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be numeric!',
                ],
            ],
        ],
        'tax' => [
            'checks'   => [
                'numeric',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be numeric!',
                ],
            ],
        ],
        'quantity' => [
            'checks'   => [
                'integer',
                'required',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer!',
                ],
            ],
        ],
        'subtract'        => [
            'checks'   => [
                'boolean',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not boolean!',
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
        'tax_class_id' => [
            'checks'   => [
                'nullable',
                'integer',
                'exists:tax_classes',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer or absent in tax_classes table!',
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
        'weight_class_id' => [
            'checks'   => [
                'integer',
                'nullable',
                'exists:weight_classes',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer or absent in weight_classes table!',
                ],
            ],
        ],
        'length' => [
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
        'width' => [
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
        'height' => [
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
        'length_class_id' => [
            'checks'   => [
                'integer',
                'nullable',
                'exists:length_classes',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer or not presents in length_classes table!',
                ],
            ],
        ],
        'shipping' => [
            'checks'   => [
                'boolean',
                'nullable'
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not boolean!',
                ],
            ],
        ],
        'ship_individually' => [
            'checks'   => [
                'boolean',
                'nullable'
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not boolean!',
                ],
            ],
        ],
        'free_shipping'     => [
            'checks'   => [
                'boolean',
                'nullable'
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not boolean!',
                ],
            ],
        ],
        'shipping_price'    => [
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
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function order_options()
    {
        return $this->belongsTo(OrderOption::class, 'order_product_id');
    }

    public function order_status()
    {
        return $this->belongsTo(OrderStatus::class, 'order_status_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function order_downloads()
    {
        return $this->hasMany(OrderDownload::class, 'order_product_id');
    }

    public function order_downloads_histories()
    {
        return $this->hasMany(OrderDownloadsHistory::class, 'order_product_id');
    }

    public static function getOrderProductOptions($order_id, $order_product_id)
    {
        $order_id = (int)$order_id;
        $order_product_id = (int)$order_product_id;
        if(!$order_id || !$order_product_id){
            return false;
        }
        /**
         * @var QueryBuilder $query
         */
        $query = OrderOption::select(
            [
                'order_options.*',
                'product_options.*',
                'product_option_values.subtract'
            ]
        )->where(
            [
                'order_options.order_id' => $order_id,
                'order_options.order_product_id' => $order_product_id,
            ]
        )->leftJoin(
            'product_option_values',
            'order_options.product_option_value_id',
            '=',
            'product_option_values.product_option_value_id'
        )->leftJoin(
            'product_options',
            'product_option_values.product_option_id',
            '=',
            'product_options.product_option_id'
        );

        Registry::extensions()->hk_extendQuery(new static,__FUNCTION__, $query, func_get_args());
        return $query->get();
    }


}
