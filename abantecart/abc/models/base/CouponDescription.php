<?php

namespace abc\models\base;

use abc\models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class CouponDescription
 *
 * @property int $coupon_id
 * @property int $language_id
 * @property string $name
 * @property string $description
 *
 * @property Coupon $coupon
 * @property Language $language
 *
 * @package abc\models
 */
class CouponDescription extends BaseModel
{
    use SoftDeletes;
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
