<?php

namespace abc\models\order;

use abc\models\BaseModel;
use abc\models\locale\Language;
use Iatstuti\Database\Support\CascadeSoftDeletes;
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

    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'coupon_id',
        'language_id',
    ];

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
