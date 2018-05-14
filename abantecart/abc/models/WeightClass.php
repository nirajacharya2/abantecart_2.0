<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcWeightClass
 * 
 * @property int $weight_class_id
 * @property float $value
 * @property string $iso_code
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 * 
 * @property \Illuminate\Database\Eloquent\Collection $ac_weight_class_descriptions
 *
 * @package App\Models
 */
class AcWeightClass extends Eloquent
{
	public $timestamps = false;

	protected $casts = [
		'value' => 'float'
	];

	protected $dates = [
		'date_added',
		'date_modified'
	];

	protected $fillable = [
		'value',
		'date_added',
		'date_modified'
	];

	public function ac_weight_class_descriptions()
	{
		return $this->hasMany(\App\Models\AcWeightClassDescription::class, 'weight_class_id');
	}
}
