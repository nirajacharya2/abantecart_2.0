<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcTaxClassDescription
 * 
 * @property int $tax_class_id
 * @property int $language_id
 * @property string $title
 * @property string $description
 * 
 * @property \App\Models\AcTaxClass $ac_tax_class
 * @property \App\Models\AcLanguage $ac_language
 *
 * @package App\Models
 */
class AcTaxClassDescription extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'tax_class_id' => 'int',
		'language_id' => 'int'
	];

	protected $fillable = [
		'title',
		'description'
	];

	public function ac_tax_class()
	{
		return $this->belongsTo(\App\Models\AcTaxClass::class, 'tax_class_id');
	}

	public function ac_language()
	{
		return $this->belongsTo(\App\Models\AcLanguage::class, 'language_id');
	}
}
