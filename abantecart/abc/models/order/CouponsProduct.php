<?php

namespace abc\models\order;

use abc\models\BaseModel;
use abc\models\catalog\Product;

/**
 * Class CouponsProduct
 *
 * @property int $coupon_product_id
 * @property int $coupon_id
 * @property int $product_id
 *
 * @property Coupon $coupon
 * @property Product $product
 *
 * @package abc\models
 */
class CouponsProduct extends BaseModel
{
    protected $primaryKey = 'coupon_product_id';
    protected $primaryKeySet = [
        'coupon_id',
        'product_id',
    ];
    public $timestamps = false;
    protected $mainClassName = Coupon::class;
    protected $mainClassKey = 'coupon_id';

    protected $casts = [
        'coupon_id'  => 'int',
        'product_id' => 'int',
    ];

    protected $fillable = [
        'coupon_id',
        'product_id',
    ];

    protected $rules = [

        'coupon_id' => [
            'checks'   => [
                'int',
                'exists:coupons',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer or absent in coupons table!',
                ],
            ],
        ],

        'product_id' => [
            'checks'   => [
                'int',
                'exists:products',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer or absent in products table!',
                ],
            ],
        ],
    ];

    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
