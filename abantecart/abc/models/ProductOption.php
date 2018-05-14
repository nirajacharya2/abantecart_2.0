<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcProductOption
 * 
 * @property int $product_option_id
 * @property int $attribute_id
 * @property int $product_id
 * @property int $group_id
 * @property int $sort_order
 * @property int $status
 * @property string $element_type
 * @property int $required
 * @property string $regexp_pattern
 * @property string $settings
 * 
 * @property \App\Models\AcProduct $ac_product
 * @property \Illuminate\Database\Eloquent\Collection $ac_product_option_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_product_option_values
 *
 * @package App\Models
 */
class AcProductOption extends Eloquent
{
	protected $primaryKey = 'product_option_id';
	public $timestamps = false;

	protected $casts = [
		'attribute_id' => 'int',
		'product_id' => 'int',
		'group_id' => 'int',
		'sort_order' => 'int',
		'status' => 'int',
		'required' => 'int'
	];

	protected $fillable = [
		'attribute_id',
		'product_id',
		'group_id',
		'sort_order',
		'status',
		'element_type',
		'required',
		'regexp_pattern',
		'settings'
	];

	public function ac_product()
	{
		return $this->belongsTo(\App\Models\AcProduct::class, 'product_id');
	}

	public function ac_product_option_descriptions()
	{
		return $this->hasMany(\App\Models\AcProductOptionDescription::class, 'product_option_id');
	}

	public function ac_product_option_values()
	{
		return $this->hasMany(\App\Models\AcProductOptionValue::class, 'product_option_id');
	}
}
