<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2022 Belavier Commerce LLC
 *
 * This source file is subject to Open Software License (OSL 3.0)
 * License details is bundled with this package in the file LICENSE.txt.
 * It is also available at this URL:
 * <http://www.opensource.org/licenses/OSL-3.0>
 *
 * UPGRADE NOTE:
 * Do not edit or add to this file if you wish to upgrade AbanteCart to newer
 * versions in the future. If you wish to customize AbanteCart for your
 * needs please refer to http://www.abantecart.com for more information.
 */
namespace abc\models\order;

use abc\models\BaseModel;
use abc\models\locale\Language;

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
