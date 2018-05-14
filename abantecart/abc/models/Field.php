<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcField
 * 
 * @property int $field_id
 * @property int $form_id
 * @property string $field_name
 * @property string $element_type
 * @property int $sort_order
 * @property string $attributes
 * @property string $settings
 * @property string $required
 * @property int $status
 * @property string $regexp_pattern
 * 
 * @property \App\Models\AcForm $ac_form
 * @property \Illuminate\Database\Eloquent\Collection $ac_field_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_field_values
 * @property \App\Models\AcFieldsGroup $ac_fields_group
 *
 * @package App\Models
 */
class AcField extends Eloquent
{
	protected $primaryKey = 'field_id';
	public $timestamps = false;

	protected $casts = [
		'form_id' => 'int',
		'sort_order' => 'int',
		'status' => 'int'
	];

	protected $fillable = [
		'form_id',
		'field_name',
		'element_type',
		'sort_order',
		'attributes',
		'settings',
		'required',
		'status',
		'regexp_pattern'
	];

	public function ac_form()
	{
		return $this->belongsTo(\App\Models\AcForm::class, 'form_id');
	}

	public function ac_field_descriptions()
	{
		return $this->hasMany(\App\Models\AcFieldDescription::class, 'field_id');
	}

	public function ac_field_values()
	{
		return $this->hasMany(\App\Models\AcFieldValue::class, 'field_id');
	}

	public function ac_fields_group()
	{
		return $this->hasOne(\App\Models\AcFieldsGroup::class, 'field_id');
	}
}
