<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcGlobalAttributesValue
 * 
 * @property int $attribute_value_id
 * @property int $attribute_id
 * @property int $sort_order
 * 
 * @property \App\Models\AcGlobalAttribute $ac_global_attribute
 *
 * @package App\Models
 */
class AcGlobalAttributesValue extends Eloquent
{
	protected $primaryKey = 'attribute_value_id';
	public $timestamps = false;

	protected $casts = [
		'attribute_id' => 'int',
		'sort_order' => 'int'
	];

	protected $fillable = [
		'attribute_id',
		'sort_order'
	];

	public function ac_global_attribute()
	{
		return $this->belongsTo(\App\Models\AcGlobalAttribute::class, 'attribute_id');
	}
}
