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
        'status'     => 'int',
        'sort_order' => 'int',
    ];

    protected $fillable = [
        'iso_code_2',
        'iso_code_3',
        'address_format',
        'status',
        'sort_order',
    ];
    protected $rules = [
        'iso_code_2'=>[
            'checks'=>[
                'string',
                'required',
                'between:1,2'
            ],
            'messages'=>[
                'language_key'=> 'error_iso_code_2',
                'language_block'=>'localisation/country',
                'default_text'=>'Iso code 2 must be between 1-2 characters',
                'section'=>'admin'
            ]
        ],
        'iso_code_3'=>[
            'checks'=>[
                'string',
                'required',
                'between:1,3'
            ],
            'messages'=>[
                'language_key'=> 'error_iso_code_3',
                'language_block'=>'localisation/country',
                'default_text'=>'Iso code 3 must be between 1-3 characters',
                'section'=>'admin'
            ]
        ],
        'address_format'=>[
            'checks'=>[
                'string',
            ],
            'messages'=>[
                'language_key'=> 'error_address_format',
                'language_block'=>'localisation/country',
                'default_text'=>'Address format must be string',
                'section'=>'admin'
            ]
        ],
        'status'=>[
            'checks'=>[
                'integer',
            ],
            'messages'=>[
                '*'=>['default_text'=>'Tax_exempt is not integer']
            ]
        ],
        'sort_order'=>[
            'checks'=>[
                'integer',
            ],
            'messages'=>[
                '*'=>['default_text'=>'Tax_exempt is not integer']
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
