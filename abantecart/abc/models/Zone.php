<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcZone
 * 
 * @property int $zone_id
 * @property int $country_id
 * @property string $code
 * @property int $status
 * @property int $sort_order
 * 
 * @property \App\Models\AcCountry $ac_country
 * @property \Illuminate\Database\Eloquent\Collection $ac_addresses
 * @property \Illuminate\Database\Eloquent\Collection $ac_tax_rates
 * @property \Illuminate\Database\Eloquent\Collection $ac_zone_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_zones_to_locations
 *
 * @package App\Models
 */
class AcZone extends Eloquent
{
	public $timestamps = false;

	protected $casts = [
		'country_id' => 'int',
		'status' => 'int',
		'sort_order' => 'int'
	];

	protected $fillable = [
		'code',
		'status',
		'sort_order'
	];

	public function ac_country()
	{
		return $this->belongsTo(\App\Models\AcCountry::class, 'country_id');
	}

	public function ac_addresses()
	{
		return $this->hasMany(\App\Models\AcAddress::class, 'zone_id');
	}

	public function ac_tax_rates()
	{
		return $this->hasMany(\App\Models\AcTaxRate::class, 'zone_id');
	}

	public function ac_zone_descriptions()
	{
		return $this->hasMany(\App\Models\AcZoneDescription::class, 'zone_id');
	}

	public function ac_zones_to_locations()
	{
		return $this->hasMany(\App\Models\AcZonesToLocation::class, 'zone_id');
	}
}
