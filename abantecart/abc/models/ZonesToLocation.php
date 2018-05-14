<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcZonesToLocation
 * 
 * @property int $zone_to_location_id
 * @property int $country_id
 * @property int $zone_id
 * @property int $location_id
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 * 
 * @property \App\Models\AcZone $ac_zone
 * @property \App\Models\AcCountry $ac_country
 * @property \App\Models\AcLocation $ac_location
 *
 * @package App\Models
 */
class AcZonesToLocation extends Eloquent
{
	protected $primaryKey = 'zone_to_location_id';
	public $timestamps = false;

	protected $casts = [
		'country_id' => 'int',
		'zone_id' => 'int',
		'location_id' => 'int'
	];

	protected $dates = [
		'date_added',
		'date_modified'
	];

	protected $fillable = [
		'country_id',
		'zone_id',
		'location_id',
		'date_added',
		'date_modified'
	];

	public function ac_zone()
	{
		return $this->belongsTo(\App\Models\AcZone::class, 'zone_id');
	}

	public function ac_country()
	{
		return $this->belongsTo(\App\Models\AcCountry::class, 'country_id');
	}

	public function ac_location()
	{
		return $this->belongsTo(\App\Models\AcLocation::class, 'location_id');
	}
}
