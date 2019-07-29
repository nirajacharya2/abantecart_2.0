<?php

namespace abc\models\order;

use abc\models\BaseModel;
use abc\models\catalog\Product;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class OrderProduct
 *
 * @property int $order_product_id
 * @property int $order_id
 * @property int $product_id
 * @property string $name
 * @property string $model
 * @property string $sku
 * @property float $price
 * @property float $total
 * @property float $tax
 * @property int $quantity
 * @property int $subtract
 *
 * @property Order $order
 * @property Product $product
 * @property \Illuminate\Database\Eloquent\Collection $order_downloads
 * @property \Illuminate\Database\Eloquent\Collection $order_downloads_histories
 *
 * @package abc\models
 */
class OrderProduct extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['order_downloads'];

    protected $primaryKey = 'order_product_id';
    public $timestamps = false;

    protected $mainClassName = Order::class;
    protected $mainClassKey = 'order_id';

    protected $casts = [
        'order_id'        => 'int',
        'product_id'      => 'int',
        'price'           => 'float',
        'total'           => 'float',
        'tax'             => 'float',
        'quantity'        => 'int',
        'subtract'        => 'int',
        'order_status_id' => 'int',
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

    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
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
}
