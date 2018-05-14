<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcGlobalAttributesDescription
 * 
 * @property int $attribute_id
 * @property int $language_id
 * @property string $name
 * @property string $placeholder
 * @property string $error_text
 * 
 * @property \App\Models\AcGlobalAttribute $ac_global_attribute
 * @property \App\Models\AcLanguage $ac_language
 *
 * @package App\Models
 */
class AcGlobalAttributesDescription extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'attribute_id' => 'int',
		'language_id' => 'int'
	];

	protected $fillable = [
		'name',
		'placeholder',
		'error_text'
	];

	public function ac_global_attribute()
	{
		return $this->belongsTo(\App\Models\AcGlobalAttribute::class, 'attribute_id');
	}

	public function ac_language()
	{
		return $this->belongsTo(\App\Models\AcLanguage::class, 'language_id');
	}
}
