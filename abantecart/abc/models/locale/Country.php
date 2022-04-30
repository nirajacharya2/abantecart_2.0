<?php

namespace abc\models\locale;

use abc\models\BaseModel;
use abc\models\customer\Address;
use Dyrynda\Database\Support\CascadeSoftDeletes;
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
 * @property \Illuminate\Database\Eloquent\Collection $addresses
 * @property \Illuminate\Database\Eloquent\Collection $country_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $zones
 * @property \Illuminate\Database\Eloquent\Collection $zones_to_locations
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
    public $timestamps = false;

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
