<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcGlobalAttribute
 * 
 * @property int $attribute_id
 * @property int $attribute_parent_id
 * @property int $attribute_group_id
 * @property int $attribute_type_id
 * @property string $element_type
 * @property int $sort_order
 * @property int $required
 * @property string $settings
 * @property int $status
 * @property string $regexp_pattern
 * 
 * @property \Illuminate\Database\Eloquent\Collection $ac_global_attributes_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_global_attributes_value_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_global_attributes_values
 *
 * @package App\Models
 */
class AcGlobalAttribute extends Eloquent
{
	protected $primaryKey = 'attribute_id';
	public $timestamps = false;

	protected $casts = [
		'attribute_parent_id' => 'int',
		'attribute_group_id' => 'int',
		'attribute_type_id' => 'int',
		'sort_order' => 'int',
		'required' => 'int',
		'status' => 'int'
	];

	protected $fillable = [
		'attribute_parent_id',
		'attribute_group_id',
		'attribute_type_id',
		'element_type',
		'sort_order',
		'required',
		'settings',
		'status',
		'regexp_pattern'
	];

	public function ac_global_attributes_descriptions()
	{
		return $this->hasMany(\App\Models\AcGlobalAttributesDescription::class, 'attribute_id');
	}

	public function ac_global_attributes_value_descriptions()
	{
		return $this->hasMany(\App\Models\AcGlobalAttributesValueDescription::class, 'attribute_id');
	}

	public function ac_global_attributes_values()
	{
		return $this->hasMany(\App\Models\AcGlobalAttributesValue::class, 'attribute_id');
	}
}
