<?php

namespace abc\models\locale;

use abc\models\BaseModel;
use abc\models\customer\Address;
use abc\models\system\TaxRate;
use Iatstuti\Database\Support\CascadeSoftDeletes;
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
        'status'     => 'int',
        'sort_order' => 'int',
    ];

    protected $fillable = [
        'code',
        'status',
        'sort_order',
    ];
    protected $rules=[
        'code'=>[
            'checks'=>[
                'string',
                'between:2,32'
            ],
            'messages'=>[
                'language_key'=> 'error_code',
                'language_block'=>'localisation/zone',
                    'default_text'=>'Code must be between 2 and 32 characters!',
                'section'=>'admin'
            ]
        ],
        'status'=>[
            'checks'=>[
                'integer'
            ],
            'messages'=>[
                '*'=>['default_text'=>'status is not integer']
            ]
        ],
        'sort_order'=>[
            'checks'=>[
                'integer'
            ],
            'messages'=>[
                '*'=>['default_text'=>'sort_order is not integer']
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
        return $this->hasOne(ZoneDescription::class, 'country_id')
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
