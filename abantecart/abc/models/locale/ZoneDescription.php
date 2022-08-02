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
namespace abc\models\locale;

use abc\models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ZoneDescription
 *
 * @property int $zone_id
 * @property int $language_id
 * @property string $name
 *
 * @property Zone $zone
 * @property Language $language
 *
 * @package abc\models
 */
class ZoneDescription extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'zone_id',
        'language_id',
    ];

    protected $touches = ['zone'];
    protected $casts = [
        'zone_id' => 'int',
        'language_id' => 'int',
    ];

    protected $fillable = [
        'name',
        'id'
    ];
    protected $rules = [
        'id' => [
            'checks' => [
                'integer',
                'required',
                'sometimes',
                'min:1'
            ],
            'messages' => [
                'integer' => [
                    'language_key' => 'error_id',
                    'language_block' => 'localisation/zone',
                    'default_text' => 'id must be integer!',
                    'section' => 'admin'
                ],
                'required' => [
                    'language_key' => 'error_id',
                    'language_block' => 'localisation/zone',
                    'default_text' => 'id required!',
                    'section' => 'admin'
                ],
                'min' => [
                    'language_key' => 'error_id',
                    'language_block' => 'localisation/zone',
                    'default_text' => 'id must be more 1!',
                    'section' => 'admin'
                ],
            ]
        ],
        'name' => [
            'checks' => [
                'string',
                'required',
                'sometimes',
                'min:2',
                'max:128'
            ],
            'messages' => [
                'min' => [
                    'language_key' => 'error_name',
                    'language_block' => 'localisation/zone',
                    'default_text' => 'Name must be more 2 characters',
                    'section' => 'admin'
                ],
                'max' => [
                    'language_key' => 'error_name',
                    'language_block' => 'localisation/zone',
                    'default_text' => 'Name must be no more than 255 characters',
                    'section' => 'admin'
                ],
                'required' => [
                    'language_key' => 'error_name',
                    'language_block' => 'localisation/zone',
                    'default_text' => 'Name required!',
                    'section' => 'admin'
                ],
                'string' => [
                    'language_key' => 'error_name',
                    'language_block' => 'localisation/zone',
                    'default_text' => 'Name must be string!',
                    'section' => 'admin'
                ],
            ]
        ]
    ];

    public function zone()
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
