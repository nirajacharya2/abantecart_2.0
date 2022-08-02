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
use abc\models\catalog\Product;
use Carbon\Carbon;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Coupon
 *
 * @property int $coupon_id
 * @property string $code
 * @property string $type
 * @property float $discount
 * @property int $logged
 * @property int $shipping
 * @property float $total
 * @property Carbon $date_start
 * @property Carbon $date_end
 * @property int $uses_total
 * @property string $uses_customer
 * @property int $status
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @property CouponDescription $description
 * @property CouponDescription $descriptions
 * @property Collection $coupons_products
 * @property Collection $orders
 *
 * @package abc\models
 */
class Coupon extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['descriptions', 'products'];
    protected $primaryKey = 'coupon_id';
    protected $casts = [
        'discount'      => 'float',
        'logged'        => 'int',
        'shipping'      => 'int',
        'total'         => 'float',
        'uses_total'    => 'int',
        'uses_customer' => 'int',
        'status'        => 'int',
    ];

    protected $dates = [
        'date_start',
        'date_end',
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'code',
        'type',
        'discount',
        'logged',
        'shipping',
        'total',
        'date_start',
        'date_end',
        'uses_total',
        'uses_customer',
        'status',
    ];

    protected $rules = [
        /** @see validate() */
        'code' => [
            'checks'   => [
                'string',
                'sometimes',
                'required',
                'between:2,10',
            ],
            'messages' => [
                '*' => [
                    'language_key'   => 'error_code',
                    'language_block' => 'sale/coupon',
                    'default_text'   => 'Coupon Code must be between 2 and 10 characters!',
                    'section'        => 'admin',
                ],
            ],
        ],

        'type' => [
            'checks'   => [
                'string',
                'required',
                'max:1',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'Coupon Type must be a string 1 character length!',
                ],
            ],
        ],

        'discount' => [
            'checks'   => [
                'numeric',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be numeric!',
                ],
            ],
        ],

        'logged'   => [
            'checks'   => [
                'boolean',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be boolean!',
                ],
            ],
        ],
        'shipping' => [
            'checks'   => [
                'boolean',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be boolean!',
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

        'date_start' => [
            'checks'   => [
                'date',
                'nullable',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be a date!',
                ],
            ],
        ],
        'date_end'   => [
            'checks'   => [
                'date',
                'nullable',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be a date!',
                ],
            ],
        ],

        'uses_total'    => [
            'checks'   => [
                'int',
                'sometimes',
                'required',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'Total coupon usage count is not integer!',
                ],
            ],
        ],
        'uses_customer' => [
            'checks'   => [
                'integer',
                'sometimes',
                'required',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'Total coupon usage count is not integer!',
                ],
            ],
        ],

        'status' => [
            'checks'   => [
                'boolean',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'status must be a boolean!',
                ],
            ],
        ],

    ];

    public function description()
    {
        return $this->hasOne(CouponDescription::class, 'coupon_id', 'coupon_id')
            ->where('language_id', '=', static::$current_language_id);
    }

    public function descriptions()
    {
        return $this->hasMany(CouponDescription::class, 'coupon_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'coupons_products', 'coupon_id', 'product_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'coupon_id');
    }
}
