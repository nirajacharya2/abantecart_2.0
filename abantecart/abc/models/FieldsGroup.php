<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcFieldsGroup
 * 
 * @property int $field_id
 * @property int $group_id
 * @property int $sort_order
 * 
 * @property \App\Models\AcField $ac_field
 * @property \App\Models\AcFormGroup $ac_form_group
 *
 * @package App\Models
 */
class AcFieldsGroup extends Eloquent
{
	protected $primaryKey = 'field_id';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'field_id' => 'int',
		'group_id' => 'int',
		'sort_order' => 'int'
	];

	protected $fillable = [
		'group_id',
		'sort_order'
	];

	public function ac_field()
	{
		return $this->belongsTo(\App\Models\AcField::class, 'field_id');
	}

	public function ac_form_group()
	{
		return $this->belongsTo(\App\Models\AcFormGroup::class, 'group_id');
	}
}
