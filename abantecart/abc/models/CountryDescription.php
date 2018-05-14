<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcCountryDescription
 * 
 * @property int $country_id
 * @property int $language_id
 * @property string $name
 * 
 * @property \App\Models\AcCountry $ac_country
 * @property \App\Models\AcLanguage $ac_language
 *
 * @package App\Models
 */
class AcCountryDescription extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'country_id' => 'int',
		'language_id' => 'int'
	];

	protected $fillable = [
		'name'
	];

	public function ac_country()
	{
		return $this->belongsTo(\App\Models\AcCountry::class, 'country_id');
	}

	public function ac_language()
	{
		return $this->belongsTo(\App\Models\AcLanguage::class, 'language_id');
	}
}
