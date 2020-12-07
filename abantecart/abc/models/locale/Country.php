<?php

namespace abc\models\locale;

use abc\models\BaseModel;
use abc\models\customer\Address;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Country
 *
 * @property int $country_id
 * @property string $iso_code_2
 * @property string $iso_code_3
 * @property string $address_format
 * @property int $status
 * @property int $sort_order
 *
 * @property Collection $addresses
 * @property CountryDescription $description
 * @property Collection $descriptions
 * @property Collection $zones
 * @property Collection $zones_to_locations
 *
 * @method static Country find(int $country_id) Country
 *
 * @package abc\models
 */
class Country extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['descriptions', 'zones', 'zones_to_locations'];

    protected $primaryKey = 'country_id';
    protected $casts = [
        'status' => 'int',
        'sort_order' => 'int',
    ];

    protected $fillable = [
        'country_id',
        'iso_code_2',
        'iso_code_3',
        'address_format',
        'status',
        'sort_order',
    ];
    protected $rules = [
        'country_id' => [
            'checks' => [
                'integer',
                'required',
                'sometimes',
                'min:1'
            ],
            'messages' => [
                'integer' => [
                    'language_key' => 'error_country_id',
                    'language_block' => 'localisation/country',
                    'default_text' => 'country_id must be integer!',
                    'section' => 'admin'
                ],
                'required' => [
                    'language_key' => 'error_country_id',
                    'language_block' => 'localisation/country',
                    'default_text' => 'country_id required!',
                    'section' => 'admin'
                ],
                'min' => [
                    'language_key' => 'error_country_id',
                    'language_block' => 'localisation/country',
                    'default_text' => 'country_id must be between 0!',
                    'section' => 'admin'
                ],
            ],
        ],
        'iso_code_2' => [
            'checks' => [
                'string',
                'size:2'
            ],
            'messages' => [
                'string' => [
                    'language_key' => 'error_iso_code_2',
                    'language_block' => 'localisation/country',
                    'default_text' => 'Iso code 2 must be string!',
                    'section' => 'admin'
                ],
                'max' => [
                    'language_key' => 'error_iso_code_2',
                    'language_block' => 'localisation/country',
                    'default_text' => 'Iso code 2 must be between 2 characters!',
                    'section' => 'admin'
                ]
            ]
        ],
        'iso_code_3' => [
            'checks' => [
                'string',
                'size:3'
            ],
            'messages' => [
                'string' => [
                    'language_key' => 'error_iso_code_3',
                    'language_block' => 'localisation/country',
                    'default_text' => 'Iso code 3 must be between 1-3 characters',
                    'section' => 'admin'
                ],
                'max' => [
                    'language_key' => 'error_iso_code_3',
                    'language_block' => 'localisation/country',
                    'default_text' => 'Iso code 3 must be between 1-3 characters',
                    'section' => 'admin'
                ]
            ]
        ],
        'address_format' => [
            'checks' => [
                'string',
            ],
            'messages' => [
                'string' => [
                    'language_key' => 'error_address_format',
                    'language_block' => 'localisation/country',
                    'default_text' => 'Address format must be string',
                    'section' => 'admin'
                ]
            ]
        ],
        'status' => [
            'checks' => [
                'integer',
            ],
            'messages' => [
                'integer' => [
                    'language_key' => 'error_status',
                    'language_block' => 'localisation/country',
                    'default_text' => 'Status is not integer!',
                    'section' => 'admin'
                ]
            ]
        ],
        'sort_order' => [
            'checks' => [
                'integer',
            ],
            'messages' => [
                'integer' => [
                    'language_key' => 'error_sort_order',
                    'language_block' => 'localisation/country',
                    'default_text' => 'Sort order is not integer!',
                    'section' => 'admin'
                ]
            ]
        ],
    ];

    public function addresses()
    {
        return $this->hasMany(Address::class, 'country_id');
    }

    public function description()
    {
        return $this->hasOne(CountryDescription::class, 'country_id')
            ->where('language_id', '=', static::$current_language_id);
    }

    public function descriptions()
    {
        return $this->hasMany(CountryDescription::class, 'country_id');
    }

    public function zones()
    {
        return $this->hasMany(Zone::class, 'country_id');
    }

    public function zones_to_locations()
    {
        return $this->hasMany(ZonesToLocation::class, 'country_id');
    }
}
