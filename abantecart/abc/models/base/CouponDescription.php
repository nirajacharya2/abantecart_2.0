<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class AcCouponDescription
 *
 * @property int $coupon_id
 * @property int $language_id
 * @property string $name
 * @property string $description
 *
 * @property \abc\models\AcCoupon $coupon
 * @property \abc\models\AcLanguage $language
 *
 * @package abc\models
 */
class CouponDescription extends AModelBase
{
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'coupon_id'   => 'int',
        'language_id' => 'int',
    ];

    protected $fillable = [
        'name',
        'description',
    ];

    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
