<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcCouponsProduct
 * 
 * @property int $coupon_product_id
 * @property int $coupon_id
 * @property int $product_id
 * 
 * @property \App\Models\AcCoupon $ac_coupon
 * @property \App\Models\AcProduct $ac_product
 *
 * @package App\Models
 */
class AcCouponsProduct extends Eloquent
{
	protected $primaryKey = 'coupon_product_id';
	public $timestamps = false;

	protected $casts = [
		'coupon_id' => 'int',
		'product_id' => 'int'
	];

	protected $fillable = [
		'coupon_id',
		'product_id'
	];

	public function ac_coupon()
	{
		return $this->belongsTo(\App\Models\AcCoupon::class, 'coupon_id');
	}

	public function ac_product()
	{
		return $this->belongsTo(\App\Models\AcProduct::class, 'product_id');
	}
}
