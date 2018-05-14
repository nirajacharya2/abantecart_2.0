<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcCouponDescription
 * 
 * @property int $coupon_id
 * @property int $language_id
 * @property string $name
 * @property string $description
 * 
 * @property \App\Models\AcCoupon $ac_coupon
 * @property \App\Models\AcLanguage $ac_language
 *
 * @package App\Models
 */
class AcCouponDescription extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'coupon_id' => 'int',
		'language_id' => 'int'
	];

	protected $fillable = [
		'name',
		'description'
	];

	public function ac_coupon()
	{
		return $this->belongsTo(\App\Models\AcCoupon::class, 'coupon_id');
	}

	public function ac_language()
	{
		return $this->belongsTo(\App\Models\AcLanguage::class, 'language_id');
	}
}
