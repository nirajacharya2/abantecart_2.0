<?php

namespace abc\models\base;

use abc\models\AModelBase;

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
 * @property \Carbon\Carbon $date_start
 * @property \Carbon\Carbon $date_end
 * @property int $uses_total
 * @property string $uses_customer
 * @property int $status
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @property \Illuminate\Database\Eloquent\Collection $coupon_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $coupons_products
 * @property \Illuminate\Database\Eloquent\Collection $orders
 *
 * @package abc\models
 */
class Coupon extends AModelBase
{
    protected $primaryKey = 'coupon_id';
    public $timestamps = false;

    protected $casts = [
        'discount'   => 'float',
        'logged'     => 'int',
        'shipping'   => 'int',
        'total'      => 'float',
        'uses_total' => 'int',
        'status'     => 'int',
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
        'date_added',
        'date_modified',
    ];

    public function coupon_descriptions()
    {
        return $this->hasMany(CouponDescription::class, 'coupon_id');
    }

    public function coupons_products()
    {
        return $this->hasMany(CouponsProduct::class, 'coupon_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'coupon_id');
    }
}
