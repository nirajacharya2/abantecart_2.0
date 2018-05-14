<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcZoneDescription
 * 
 * @property int $zone_id
 * @property int $language_id
 * @property string $name
 * 
 * @property \App\Models\AcZone $ac_zone
 * @property \App\Models\AcLanguage $ac_language
 *
 * @package App\Models
 */
class AcZoneDescription extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'zone_id' => 'int',
		'language_id' => 'int'
	];

	protected $fillable = [
		'name'
	];

	public function ac_zone()
	{
		return $this->belongsTo(\App\Models\AcZone::class, 'zone_id');
	}

	public function ac_language()
	{
		return $this->belongsTo(\App\Models\AcLanguage::class, 'language_id');
	}
}
