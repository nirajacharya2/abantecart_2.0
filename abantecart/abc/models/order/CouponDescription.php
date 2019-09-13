<?php

namespace abc\models\order;

use abc\models\BaseModel;
use abc\models\locale\Language;
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

    protected $mainClassName = Coupon::class;
    protected $mainClassKey = 'coupon_id';

    protected $casts = [
        'coupon_id'   => 'int',
        'language_id' => 'int',
    ];

    protected $fillable = [
        'coupon_id',
        'language_id',
        'name',
        'description',
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

        'language_id' => [
            'checks'   => [
                'int',
                'exists:languages',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer or absent in languages table!',
                ],
            ],
        ],

        'name'        => [
            'checks'   => [
                'string',
                'min:2',
                'max:128',
                'required',
            ],
            'messages' => [
                '*' => [
                    'language_key'   => 'error_name',
                    'language_block' => 'sale/coupon',
                    'default_text'   => 'Coupon name must be between :min and :max characters!',
                    'section'        => 'admin',
                ],
            ],
        ],
        'description' => [
            'checks'   => [
                'string',
                'min:2',
                'max:1500',
                'required',
            ],
            'messages' => [
                '*' => [
                    'language_key'   => 'error_description',
                    'language_block' => 'sale/coupon',
                    'default_text'   => 'Coupon description must be between 2 characters!',
                    'section'        => 'admin',
                ],
            ],
        ],
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
