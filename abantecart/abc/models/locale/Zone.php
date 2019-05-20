<?php

namespace abc\models\locale;

use abc\models\BaseModel;
use abc\models\customer\Address;
use abc\models\system\TaxRate;
use Iatstuti\Database\Support\CascadeSoftDeletes;
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
 * @property \Illuminate\Database\Eloquent\Collection $addresses
 * @property \Illuminate\Database\Eloquent\Collection $tax_rates
 * @property \Illuminate\Database\Eloquent\Collection $zone_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $zones_to_locations
 *
 * @package abc\models
 */
class Zone extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['descriptions'];
    public $timestamps = false;
    protected $primaryKey = 'zone_id';

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

    public function descriptions()
    {
        return $this->hasMany(ZoneDescription::class, 'zone_id');
    }

    public function locations()
    {
        return $this->hasMany(ZonesToLocation::class, 'zone_id');
    }
}
