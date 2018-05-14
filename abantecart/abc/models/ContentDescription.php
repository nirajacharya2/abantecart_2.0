<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcContentDescription
 * 
 * @property int $content_id
 * @property int $language_id
 * @property string $name
 * @property string $title
 * @property string $description
 * @property string $content
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 * 
 * @property \App\Models\AcContent $ac_content
 * @property \App\Models\AcLanguage $ac_language
 *
 * @package App\Models
 */
class AcContentDescription extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'content_id' => 'int',
		'language_id' => 'int'
	];

	protected $dates = [
		'date_added',
		'date_modified'
	];

	protected $fillable = [
		'name',
		'title',
		'description',
		'content',
		'date_added',
		'date_modified'
	];

	public function ac_content()
	{
		return $this->belongsTo(\App\Models\AcContent::class, 'content_id');
	}

	public function ac_language()
	{
		return $this->belongsTo(\App\Models\AcLanguage::class, 'language_id');
	}
}
