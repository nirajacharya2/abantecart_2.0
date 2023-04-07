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

namespace abc\extensions\incentive\models;

use abc\models\BaseModel;
use abc\models\casts\Html;
use abc\models\locale\Language;
use Carbon\Carbon;

/**
 * Class IncentiveDescription
 *
 * @property int $incentive_id
 * @property int $language_id
 * @property string $name
 * @property string $description
 * @property string $description_short
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @property Incentive $incentive
 * @property Language $language
 *
 * @package abc\models
 */
class IncentiveDescription extends BaseModel
{
    protected $primaryKeySet = [
        'incentive_id',
        'language_id',
    ];

    protected $casts = [
        'incentive_id'  => 'int',
        'language_id'   => 'int',
        'description'   => Html::class,
        'date_added'    => 'datetime',
        'date_modified' => 'datetime'
    ];

    protected $fillable = [
        'incentive_id',
        'language_id',
        'name',
        'description_short',
        'description',
        'date_added',
        'date_modified',
    ];
    protected $rules = [
        'incentive_id'      => [
            'checks'   => [
                'integer',
                'required',
                'sometimes'
            ],
            'messages' => [
                '*' => ['default_text' => 'Incentive ID is not Integer!'],
            ],
        ],
        'language_id'       => [
            'checks'   => [
                'integer',
                'required'
            ],
            'messages' => [
                '*' => ['default_text' => 'Language ID is not Integer!'],
            ],
        ],
        'name'              => [
            'checks'   => [
                'string',
                'between:1,255',
                'required_without:incentive_id'
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be a string between 1 abd 255 characters!',
                ],
            ],
        ],
        'description'       => [
            'checks'   => [
                'string',
                'max:16777215',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be a string less than 16,777,215 characters!',
                ],
            ],
        ],
        'description_short' => [
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

    public function incentive()
    {
        return $this->belongsTo(Incentive::class, 'incentive_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}

