<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcTaxRateDescription
 * 
 * @property int $tax_rate_id
 * @property int $language_id
 * @property string $description
 * 
 * @property \App\Models\AcTaxRate $ac_tax_rate
 * @property \App\Models\AcLanguage $ac_language
 *
 * @package App\Models
 */
class AcTaxRateDescription extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'tax_rate_id' => 'int',
		'language_id' => 'int'
	];

	protected $fillable = [
		'description'
	];

	public function ac_tax_rate()
	{
		return $this->belongsTo(\App\Models\AcTaxRate::class, 'tax_rate_id');
	}

	public function ac_language()
	{
		return $this->belongsTo(\App\Models\AcLanguage::class, 'language_id');
	}
}
