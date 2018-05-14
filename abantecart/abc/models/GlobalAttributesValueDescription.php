<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcGlobalAttributesValueDescription
 * 
 * @property int $attribute_value_id
 * @property int $attribute_id
 * @property int $language_id
 * @property string $value
 * 
 * @property \App\Models\AcGlobalAttribute $ac_global_attribute
 * @property \App\Models\AcLanguage $ac_language
 *
 * @package App\Models
 */
class AcGlobalAttributesValueDescription extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'attribute_value_id' => 'int',
		'attribute_id' => 'int',
		'language_id' => 'int'
	];

	protected $fillable = [
		'value'
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
