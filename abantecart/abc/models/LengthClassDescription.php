<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcLengthClassDescription
 * 
 * @property int $length_class_id
 * @property int $language_id
 * @property string $title
 * @property string $unit
 * 
 * @property \App\Models\AcLanguage $ac_language
 *
 * @package App\Models
 */
class AcLengthClassDescription extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'length_class_id' => 'int',
		'language_id' => 'int'
	];

	protected $fillable = [
		'title',
		'unit'
	];

	public function ac_language()
	{
		return $this->belongsTo(\App\Models\AcLanguage::class, 'language_id');
	}
}
