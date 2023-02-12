<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2023 Belavier Commerce LLC
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

namespace abc\extensions\banner_manager\models;

use abc\models\BaseModel;
use abc\models\locale\Language;
use Carbon\Carbon;

/**
 * Class BannerDescription
 *
 * @property int $banner_id
 * @property int $language_id
 * @property string $name
 * @property string $description
 * @property string $meta
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @property Banner $banner
 * @property Language $language
 *
 * @package abc\models
 */
class BannerDescription extends BaseModel
{
    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'banner_id',
        'language_id',
    ];

    protected $casts = [
        'banner_id'     => 'int',
        'language_id'   => 'int',
        'date_added'    => 'datetime',
        'date_modified' => 'datetime'
    ];

    protected $fillable = [
        'language_id',
        'name',
        'description',
        'meta'
    ];
    protected $rules = [

        'banner_id'   => [
            'checks'   => [
                'integer',
                'required'
            ],
            'messages' => [
                '*' => ['default_text' => 'Banner ID is not Integer!'],
            ],
        ],
        'language_id' => [
            'checks'   => [
                'integer',
                'required'
            ],
            'messages' => [
                '*' => ['default_text' => 'Language ID is not Integer!'],
            ],
        ],
        'name'        => [
            'checks'   => [
                'string',
                'max:255',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be a string between 1 abd 255 characters!',
                ],
            ],
        ],
        'meta'        => [
            'checks'   => [
                'string',
                'max:1500',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be a string less than 1500 characters!',
                ],
            ],
        ]
    ];

    public function banner()
    {
        return $this->belongsTo(Banner::class, 'banner_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
