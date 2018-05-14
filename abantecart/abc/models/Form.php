<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcForm
 * 
 * @property int $form_id
 * @property string $form_name
 * @property string $controller
 * @property string $success_page
 * @property int $status
 * 
 * @property \Illuminate\Database\Eloquent\Collection $ac_fields
 * @property \Illuminate\Database\Eloquent\Collection $ac_form_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_form_groups
 * @property \Illuminate\Database\Eloquent\Collection $ac_pages_forms
 *
 * @package App\Models
 */
class AcForm extends Eloquent
{
	protected $primaryKey = 'form_id';
	public $timestamps = false;

	protected $casts = [
		'status' => 'int'
	];

	protected $fillable = [
		'form_name',
		'controller',
		'success_page',
		'status'
	];

	public function ac_fields()
	{
		return $this->hasMany(\App\Models\AcField::class, 'form_id');
	}

	public function ac_form_descriptions()
	{
		return $this->hasMany(\App\Models\AcFormDescription::class, 'form_id');
	}

	public function ac_form_groups()
	{
		return $this->hasMany(\App\Models\AcFormGroup::class, 'form_id');
	}

	public function ac_pages_forms()
	{
		return $this->hasMany(\App\Models\AcPagesForm::class, 'form_id');
	}
}
