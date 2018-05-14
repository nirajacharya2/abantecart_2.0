<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcFieldsGroupDescription
 * 
 * @property int $group_id
 * @property string $name
 * @property string $description
 * @property int $language_id
 * 
 * @property \App\Models\AcFormGroup $ac_form_group
 * @property \App\Models\AcLanguage $ac_language
 *
 * @package App\Models
 */
class AcFieldsGroupDescription extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'group_id' => 'int',
		'language_id' => 'int'
	];

	protected $fillable = [
		'name',
		'description'
	];

	public function ac_form_group()
	{
		return $this->belongsTo(\App\Models\AcFormGroup::class, 'group_id');
	}

	public function ac_language()
	{
		return $this->belongsTo(\App\Models\AcLanguage::class, 'language_id');
	}
}
