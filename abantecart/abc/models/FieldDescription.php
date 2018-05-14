<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcFieldDescription
 * 
 * @property int $field_id
 * @property string $name
 * @property string $description
 * @property int $language_id
 * @property string $error_text
 * 
 * @property \App\Models\AcField $ac_field
 * @property \App\Models\AcLanguage $ac_language
 *
 * @package App\Models
 */
class AcFieldDescription extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'field_id' => 'int',
		'language_id' => 'int'
	];

	protected $fillable = [
		'name',
		'description',
		'error_text'
	];

	public function ac_field()
	{
		return $this->belongsTo(\App\Models\AcField::class, 'field_id');
	}

	public function ac_language()
	{
		return $this->belongsTo(\App\Models\AcLanguage::class, 'language_id');
	}
}
