<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcWeightClassDescription
 * 
 * @property int $weight_class_id
 * @property int $language_id
 * @property string $title
 * @property string $unit
 * 
 * @property \App\Models\AcWeightClass $ac_weight_class
 * @property \App\Models\AcLanguage $ac_language
 *
 * @package App\Models
 */
class AcWeightClassDescription extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'weight_class_id' => 'int',
		'language_id' => 'int'
	];

	protected $fillable = [
		'title',
		'unit'
	];

	public function ac_weight_class()
	{
		return $this->belongsTo(\App\Models\AcWeightClass::class, 'weight_class_id');
	}

	public function ac_language()
	{
		return $this->belongsTo(\App\Models\AcLanguage::class, 'language_id');
	}
}
