<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class AcZonesToLocation
 *
 * @property int                    $zone_to_location_id
 * @property int                    $country_id
 * @property int                    $zone_id
 * @property int                    $location_id
 * @property \Carbon\Carbon         $date_added
 * @property \Carbon\Carbon         $date_modified
 *
 * @property \abc\models\AcZone     $zone
 * @property \abc\models\AcCountry  $country
 * @property \abc\models\AcLocation $location
 *
 * @package abc\models
 */
class ZonesToLocation extends AModelBase
{
    protected $primaryKey = 'zone_to_location_id';
    public $timestamps = false;

    protected $casts = [
        'country_id'  => 'int',
        'zone_id'     => 'int',
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
