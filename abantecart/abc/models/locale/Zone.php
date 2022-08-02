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
use abc\models\customer\Address;
use abc\models\system\TaxRate;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Zone
 *
 * @property int $zone_id
 * @property int $country_id
 * @property string $code
 * @property int $status
 * @property int $sort_order
 *
 * @property Country $country
 * @property Collection $addresses
 * @property Collection $tax_rates
 * @property ZoneDescription $description
 * @property Collection $descriptions
 * @property Collection $zones_to_locations
 *
 * @method static Zone find(int $zone_id) Zone
 *
 * @package abc\models
 */
class Zone extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['descriptions'];
    protected $primaryKey = 'zone_id';

    protected $touches = ['country', 'addresses', 'tax_rates', 'locations'];

    protected $casts = [
        'country_id' => 'int',
        'status' => 'int',
        'sort_order' => 'int',
    ];

    protected $fillable = [
        'code',
        'status',
        'sort_order',
        'zone_id'
    ];
    protected $rules = [
        'zone_id' => [
            'checks' => [
                'integer',
                'required',
                'sometimes',
                'min:1'
            ],
            'messages' => [
                'integer' => [
                    'language_key' => 'error_zone_id',
                    'language_block' => 'localisation/zone',
                    'default_text' => 'Zone id must be integer!',
                    'section' => 'admin'
                ],
                'required' => [
                    'language_key' => 'error_zone_id',
                    'language_block' => 'localisation/zone',
                    'default_text' => 'Zone id required!',
                    'section' => 'admin'
                ],
                'min' => [
                    'language_key' => 'error_zone_id',
                    'language_block' => 'localisation/zone',
                    'default_text' => 'Zone id must be more 1!',
                    'section' => 'admin'
                ],
            ]
        ],
        'code' => [
            'checks' => [
                'string',
                'min:2',
                'max:32'
            ],
            'messages' => [
                'min' => [
                    'language_key' => 'error_code',
                    'language_block' => 'localisation/zone',
                    'default_text' => 'Code must be more 2 characters',
                    'section' => 'admin'
                ],
                'max' => [
                    'language_key' => 'error_code',
                    'language_block' => 'localisation/zone',
                    'default_text' => 'Code must be no more than 32 characters',
                    'section' => 'admin'
                ],
                'string' => [
                    'language_key' => 'error_code',
                    'language_block' => 'localisation/zone',
                    'default_text' => 'Code must be string!',
                    'section' => 'admin'
                ],
            ]
        ],
        'status' => [
            'checks' => [
                'integer'
            ],
            'messages' => [
                'integer' => [
                    'language_key' => 'error_status',
                    'language_block' => 'localisation/zone',
                    'default_text' => 'status must be integer!',
                    'section' => 'admin'
                ],
            ]
        ],
        'sort_order' => [
            'checks' => [
                'integer'
            ],
            'messages' => [
                'integer' => [
                    'language_key' => 'error_sort_order',
                    'language_block' => 'localisation/zone',
                    'default_text' => 'sort order must be integer!',
                    'section' => 'admin'
                ],
            ]
        ]
    ];

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function addresses()
    {
        return $this->hasMany(Address::class, 'zone_id');
    }

    public function tax_rates()
    {
        return $this->hasMany(TaxRate::class, 'zone_id');
    }

    public function description()
    {
        return $this->hasOne(ZoneDescription::class, 'zone_id')
            ->where('language_id', '=', static::$current_language_id);
    }

    public function descriptions()
    {
        return $this->hasMany(ZoneDescription::class, 'zone_id');
    }

    public function locations()
    {
        return $this->hasMany(ZonesToLocation::class, 'zone_id');
    }
}
