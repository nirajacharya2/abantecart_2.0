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
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ZonesToLocation
 *
 * @property int $zone_to_location_id
 * @property int $country_id
 * @property int $zone_id
 * @property int $location_id
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @property Zone $zone
 * @property Country $country
 * @property Location $location
 *
 * @package abc\models
 */
class ZonesToLocation extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'zone_to_location_id';
    public $timestamps = false;

    protected $casts = [
        'country_id' => 'int',
        'zone_id' => 'int',
        'location_id' => 'int',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'country_id',
        'zone_id',
        'location_id',
        'date_added',
        'date_modified',
        'zone_to_location_id'
    ];
    protected $rules = [
        'zone_to_location_id'=>[
            'checks' => [
                'integer',
                'required',
                'sometimes',
                'min:1'
            ],
            'messages' => [
                'integer' => [
                    'language_key' => 'error_zone_to_location_id',
                    'language_block' => 'localisation/zone',
                    'default_text' => 'zone to location id must be integer!',
                    'section' => 'admin'
                ],
                'required' => [
                    'language_key' => 'error_zone_to_location_id',
                    'language_block' => 'localisation/zone',
                    'default_text' => 'zone to location id required!',
                    'section' => 'admin'
                ],
                'min' => [
                    'language_key' => 'error_zone_to_location_id',
                    'language_block' => 'localisation/zone',
                    'default_text' => 'zone to location id must be more 1!',
                    'section' => 'admin'
                ],
            ]
        ],
        'country_id' => [
            'checks' => [
                'required',
                'sometimes',
                'integer'
            ],
            'messages' => [
                'integer' => [
                    'language_key' => 'error_country_id',
                    'language_block' => 'localisation/zone',
                    'default_text' => 'country id must be integer!',
                    'section' => 'admin'
                ],
                'required' => [
                    'language_key' => 'error_country_id',
                    'language_block' => 'localisation/zone',
                    'default_text' => 'country id required!',
                    'section' => 'admin'
                ],
            ]
        ],
        'zone_id' => [
            'checks' => [
                'required',
                'integer',
                'sometimes'
            ],
            'messages' => [
                'integer' => [
                    'language_key' => 'error_zone_id',
                    'language_block' => 'localisation/zone',
                    'default_text' => 'zone id must be integer!',
                    'section' => 'admin'
                ],
                'required' => [
                    'language_key' => 'error_zone_id',
                    'language_block' => 'localisation/zone',
                    'default_text' => 'zone id required!',
                    'section' => 'admin'
                ],
            ]
        ],
        'location_id' => [
            'checks' => [
                'required',
                'sometimes',
                'integer'
            ],
            'messages' => [
                'integer' => [
                    'language_key' => 'error_location_id',
                    'language_block' => 'localisation/zone',
                    'default_text' => 'location id must be integer!',
                    'section' => 'admin'
                ],
                'required' => [
                    'language_key' => 'error_location_id',
                    'language_block' => 'localisation/zone',
                    'default_text' => 'location id required!',
                    'section' => 'admin'
                ],
            ]
        ]
    ];

    public function zone()
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}
