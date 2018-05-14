<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcLanguageDefinition
 * 
 * @property int $language_definition_id
 * @property int $language_id
 * @property bool $section
 * @property string $block
 * @property string $language_key
 * @property string $language_value
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 * 
 * @property \App\Models\AcLanguage $ac_language
 *
 * @package App\Models
 */
class AcLanguageDefinition extends Eloquent
{
	public $timestamps = false;

	protected $casts = [
		'language_id' => 'int',
		'section' => 'bool'
	];

	protected $dates = [
		'date_added',
		'date_modified'
	];

	protected $fillable = [
		'language_value',
		'date_added',
		'date_modified'
	];

	public function ac_language()
	{
		return $this->belongsTo(\App\Models\AcLanguage::class, 'language_id');
	}
}
