<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcCoupon
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
 * @property \Illuminate\Database\Eloquent\Collection $ac_coupon_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_coupons_products
 * @property \Illuminate\Database\Eloquent\Collection $ac_orders
 *
 * @package App\Models
 */
class AcCoupon extends Eloquent
{
	protected $primaryKey = 'coupon_id';
	public $timestamps = false;

	protected $casts = [
		'discount' => 'float',
		'logged' => 'int',
		'shipping' => 'int',
		'total' => 'float',
		'uses_total' => 'int',
		'status' => 'int'
	];

	protected $dates = [
		'date_start',
		'date_end',
		'date_added',
		'date_modified'
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
		'date_modified'
	];

	public function ac_coupon_descriptions()
	{
		return $this->hasMany(\App\Models\AcCouponDescription::class, 'coupon_id');
	}

	public function ac_coupons_products()
	{
		return $this->hasMany(\App\Models\AcCouponsProduct::class, 'coupon_id');
	}

	public function ac_orders()
	{
		return $this->hasMany(\App\Models\AcOrder::class, 'coupon_id');
	}
}
