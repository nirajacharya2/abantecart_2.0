<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcResourceDescription
 * 
 * @property int $resource_id
 * @property int $language_id
 * @property string $name
 * @property string $title
 * @property string $description
 * @property string $resource_path
 * @property string $resource_code
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 * 
 * @property \App\Models\AcResourceLibrary $ac_resource_library
 * @property \App\Models\AcLanguage $ac_language
 *
 * @package App\Models
 */
class AcResourceDescription extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'resource_id' => 'int',
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
		'resource_path',
		'resource_code',
		'date_added',
		'date_modified'
	];

	public function ac_resource_library()
	{
		return $this->belongsTo(\App\Models\AcResourceLibrary::class, 'resource_id');
	}

	public function ac_language()
	{
		return $this->belongsTo(\App\Models\AcLanguage::class, 'language_id');
	}
}
