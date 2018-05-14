<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcTaxClass
 * 
 * @property int $tax_class_id
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 * 
 * @property \Illuminate\Database\Eloquent\Collection $ac_tax_class_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_tax_rates
 *
 * @package App\Models
 */
class AcTaxClass extends Eloquent
{
	protected $primaryKey = 'tax_class_id';
	public $timestamps = false;

	protected $dates = [
		'date_added',
		'date_modified'
	];

	protected $fillable = [
		'date_added',
		'date_modified'
	];

	public function ac_tax_class_descriptions()
	{
		return $this->hasMany(\App\Models\AcTaxClassDescription::class, 'tax_class_id');
	}

	public function ac_tax_rates()
	{
		return $this->hasMany(\App\Models\AcTaxRate::class, 'tax_class_id');
	}
}
