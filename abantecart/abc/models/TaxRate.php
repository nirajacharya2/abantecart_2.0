<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcTaxRate
 * 
 * @property int $tax_rate_id
 * @property int $location_id
 * @property int $zone_id
 * @property int $tax_class_id
 * @property int $priority
 * @property float $rate
 * @property string $rate_prefix
 * @property string $threshold_condition
 * @property float $threshold
 * @property string $tax_exempt_groups
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 * 
 * @property \App\Models\AcTaxClass $ac_tax_class
 * @property \App\Models\AcLocation $ac_location
 * @property \App\Models\AcZone $ac_zone
 * @property \Illuminate\Database\Eloquent\Collection $ac_tax_rate_descriptions
 *
 * @package App\Models
 */
class AcTaxRate extends Eloquent
{
	protected $primaryKey = 'tax_rate_id';
	public $timestamps = false;

	protected $casts = [
		'location_id' => 'int',
		'zone_id' => 'int',
		'tax_class_id' => 'int',
		'priority' => 'int',
		'rate' => 'float',
		'threshold' => 'float'
	];

	protected $dates = [
		'date_added',
		'date_modified'
	];

	protected $fillable = [
		'location_id',
		'zone_id',
		'tax_class_id',
		'priority',
		'rate',
		'rate_prefix',
		'threshold_condition',
		'threshold',
		'tax_exempt_groups',
		'date_added',
		'date_modified'
	];

	public function ac_tax_class()
	{
		return $this->belongsTo(\App\Models\AcTaxClass::class, 'tax_class_id');
	}

	public function ac_location()
	{
		return $this->belongsTo(\App\Models\AcLocation::class, 'location_id');
	}

	public function ac_zone()
	{
		return $this->belongsTo(\App\Models\AcZone::class, 'zone_id');
	}

	public function ac_tax_rate_descriptions()
	{
		return $this->hasMany(\App\Models\AcTaxRateDescription::class, 'tax_rate_id');
	}
}
