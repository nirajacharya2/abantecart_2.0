<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcFormGroup
 * 
 * @property int $group_id
 * @property string $group_name
 * @property int $form_id
 * @property int $sort_order
 * @property int $status
 * 
 * @property \App\Models\AcForm $ac_form
 * @property \Illuminate\Database\Eloquent\Collection $ac_fields_group_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_fields_groups
 *
 * @package App\Models
 */
class AcFormGroup extends Eloquent
{
	protected $primaryKey = 'group_id';
	public $timestamps = false;

	protected $casts = [
		'form_id' => 'int',
		'sort_order' => 'int',
		'status' => 'int'
	];

	protected $fillable = [
		'group_name',
		'form_id',
		'sort_order',
		'status'
	];

	public function ac_form()
	{
		return $this->belongsTo(\App\Models\AcForm::class, 'form_id');
	}

	public function ac_fields_group_descriptions()
	{
		return $this->hasMany(\App\Models\AcFieldsGroupDescription::class, 'group_id');
	}

	public function ac_fields_groups()
	{
		return $this->hasMany(\App\Models\AcFieldsGroup::class, 'group_id');
	}
}
