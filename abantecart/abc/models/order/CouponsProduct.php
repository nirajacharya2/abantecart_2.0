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
        'product_id'
    ];
    public $timestamps = false;

    protected $casts = [
        'coupon_id'  => 'int',
        'product_id' => 'int',
    ];

    protected $fillable = [
        'coupon_id',
        'product_id',
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
