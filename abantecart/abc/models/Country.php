<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcCountry
 * 
 * @property int $country_id
 * @property string $iso_code_2
 * @property string $iso_code_3
 * @property string $address_format
 * @property int $status
 * @property int $sort_order
 * 
 * @property \Illuminate\Database\Eloquent\Collection $ac_addresses
 * @property \Illuminate\Database\Eloquent\Collection $ac_country_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_zones
 * @property \Illuminate\Database\Eloquent\Collection $ac_zones_to_locations
 *
 * @package App\Models
 */
class AcCountry extends Eloquent
{
	protected $primaryKey = 'country_id';
	public $timestamps = false;

	protected $casts = [
		'status' => 'int',
		'sort_order' => 'int'
	];

	protected $fillable = [
		'iso_code_2',
		'iso_code_3',
		'address_format',
		'status',
		'sort_order'
	];

	public function ac_addresses()
	{
		return $this->hasMany(\App\Models\AcAddress::class, 'country_id');
	}

	public function ac_country_descriptions()
	{
		return $this->hasMany(\App\Models\AcCountryDescription::class, 'country_id');
	}

	public function ac_zones()
	{
		return $this->hasMany(\App\Models\AcZone::class, 'country_id');
	}

	public function ac_zones_to_locations()
	{
		return $this->hasMany(\App\Models\AcZonesToLocation::class, 'country_id');
	}
}
