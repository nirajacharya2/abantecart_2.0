<?php

namespace abc\models\catalog;

use abc\models\BaseModel;
use abc\models\customer\CustomerGroup;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ProductSpecial
 *
 * @property int $product_special_id
 * @property int $product_id
 * @property int $customer_group_id
 * @property int $priority
 * @property float $price
 * @property Carbon $date_start
 * @property Carbon $date_end
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @property Product $product
 *
 * @method static ProductSpecial find(int $product_id) ProductSpecial
 *
 * @package abc\models
 */
class ProductSpecial extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'product_special_id';
    protected $mainClassName = Product::class;
    protected $mainClassKey = 'product_id';

    protected $touches = ['product'];

    protected $casts = [
        'product_id'        => 'int',
        'customer_group_id' => 'int',
        'priority'          => 'int',
        'price'             => 'float',
    ];

    protected $dates = [
        'date_start',
        'date_end',
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'product_id',
        'customer_group_id',
        'priority',
        'price',
        'date_start',
        'date_end',
    ];

    protected $rules = [
        /** @see validate() */
        'product_id' => [
            'checks'   => [
                'integer',
                'sometimes',
                'required',
                'exists:products',
            ],
            'messages' => [
                '*' => ['default_text' => 'Product ID is not Integer or absent in the products table!'],
            ],
        ],

        'customer_group_id' => [
            'checks'   => [
                'integer',
                'sometimes',
                'required',
                'exists:customer_groups',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'Customer Group ID must be an integer or NULL or presents in the customer_groups table!',
                ],
            ],
        ],

        'priority' => [
            'checks'   => [
                'integer',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'Priority must be an integer!',
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

        'date_start' => [
            'checks'   => [
                'date_format:Y-m-d H:i:s',
                'nullable',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute. Wrong date format!',
                ],
            ],
        ],

        'date_end' => [
            'checks'   => [
                'date_format:Y-m-d H:i:s',
                'nullable',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute. Wrong date format!',
                ],
            ],
        ],
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function customer_group()
    {
        return $this->belongsTo(CustomerGroup::class, 'customer_group_id');
    }
}
