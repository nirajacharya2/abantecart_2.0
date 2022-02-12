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
 *
 */
namespace abc\models\catalog;

use abc\models\BaseModel;
use abc\models\customer\CustomerGroup;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    /**
     * @return BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * @return BelongsTo
     */
    public function customer_group()
    {
        return $this->belongsTo(CustomerGroup::class, 'customer_group_id');
    }
}
