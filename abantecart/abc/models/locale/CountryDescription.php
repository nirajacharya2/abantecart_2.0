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
 * Class CountryDescription
 *
 * @property int $country_id
 * @property int $language_id
 * @property string $name
 *
 * @property Country $country
 * @property Language $language
 *
 * @package abc\models
 */
class CountryDescription extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'country_id',
        'language_id',
    ];

    protected $touches = ['country'];

    protected $casts = [
        'country_id' => 'int',
        'language_id' => 'int',
    ];

    protected $fillable = [
        'name',
        'language_id',
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
                    'language_key' => 'error_country_description_id',
                    'language_block' => 'localisation/country',
                    'default_text' => 'id must be integer!',
                    'section' => 'admin'
                ],
                'required' => [
                    'language_key' => 'error_country_description_id',
                    'language_block' => 'localisation/country',
                    'default_text' => 'id required!',
                    'section' => 'admin'
                ],
                'min' => [
                    'language_key' => 'error_country_description_id',
                    'language_block' => 'localisation/country',
                    'default_text' => 'id must be more 0!',
                    'section' => 'admin'
                ],
            ],
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
                    'language_block' => 'localisation/country',
                    'default_text' => 'Country Name must be more 2!',
                    'section' => 'admin'
                ],
                'max' => [
                    'language_key' => 'error_name',
                    'language_block' => 'localisation/country',
                    'default_text' => 'Country Name must not exceed 128 characters!',
                    'section' => 'admin'
                ],
                'string' => [
                    'language_key' => 'error_name',
                    'language_block' => 'localisation/country',
                    'default_text' => 'Country Name must be string!',
                    'section' => 'admin'
                ],
                'required' => [
                    'language_key' => 'error_name',
                    'language_block' => 'localisation/country',
                    'default_text' => 'Country Name required!',
                    'section' => 'admin'
                ],
            ],
        ],
        'language_id' => [
            'checks' => [
                'integer',
                'required',
                'sometimes',
                'min:1'
            ],
            'messages' => [
                'integer' => [
                    'language_key' => 'error_language_id',
                    'language_block' => 'localisation/country',
                    'default_text' => 'language_id must be integer!',
                    'section' => 'admin'
                ],
                'required' => [
                    'language_key' => 'error_language_id',
                    'language_block' => 'localisation/country',
                    'default_text' => 'language_id required!',
                    'section' => 'admin'
                ],
                'min' => [
                    'language_key' => 'error_language_id',
                    'language_block' => 'localisation/country',
                    'default_text' => 'language_id must be more 0!',
                    'section' => 'admin'
                ],
            ],
        ]
    ];

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
