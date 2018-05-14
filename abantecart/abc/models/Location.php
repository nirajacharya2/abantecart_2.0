<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcLocation
 * 
 * @property int $location_id
 * @property string $name
 * @property string $description
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 * 
 * @property \Illuminate\Database\Eloquent\Collection $ac_tax_rates
 * @property \Illuminate\Database\Eloquent\Collection $ac_zones_to_locations
 *
 * @package App\Models
 */
class AcLocation extends Eloquent
{
	protected $primaryKey = 'location_id';
	public $timestamps = false;

	protected $dates = [
		'date_added',
		'date_modified'
	];

	protected $fillable = [
		'name',
		'description',
		'date_added',
		'date_modified'
	];

	public function ac_tax_rates()
	{
		return $this->hasMany(\App\Models\AcTaxRate::class, 'location_id');
	}

	public function ac_zones_to_locations()
	{
		return $this->hasMany(\App\Models\AcZonesToLocation::class, 'location_id');
	}
}
