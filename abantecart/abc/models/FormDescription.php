<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcFormDescription
 * 
 * @property int $form_id
 * @property int $language_id
 * @property string $description
 * 
 * @property \App\Models\AcForm $ac_form
 * @property \App\Models\AcLanguage $ac_language
 *
 * @package App\Models
 */
class AcFormDescription extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'form_id' => 'int',
		'language_id' => 'int'
	];

	protected $fillable = [
		'description'
	];

	public function ac_form()
	{
		return $this->belongsTo(\App\Models\AcForm::class, 'form_id');
	}

	public function ac_language()
	{
		return $this->belongsTo(\App\Models\AcLanguage::class, 'language_id');
	}
}
