<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcDatasetValue
 * 
 * @property int $dataset_column_id
 * @property int $value_integer
 * @property float $value_float
 * @property string $value_varchar
 * @property string $value_text
 * @property \Carbon\Carbon $value_timestamp
 * @property bool $value_boolean
 * @property int $value_sort_order
 * @property int $row_id
 * 
 * @property \App\Models\AcDatasetDefinition $ac_dataset_definition
 *
 * @package App\Models
 */
class AcDatasetValue extends Eloquent
{
	protected $primaryKey = 'value_sort_order';
	public $timestamps = false;

	protected $casts = [
		'dataset_column_id' => 'int',
		'value_integer' => 'int',
		'value_float' => 'float',
		'value_boolean' => 'bool',
		'row_id' => 'int'
	];

	protected $dates = [
		'value_timestamp'
	];

	protected $fillable = [
		'dataset_column_id',
		'value_integer',
		'value_float',
		'value_varchar',
		'value_text',
		'value_timestamp',
		'value_boolean',
		'row_id'
	];

	public function ac_dataset_definition()
	{
		return $this->belongsTo(\App\Models\AcDatasetDefinition::class, 'dataset_column_id');
	}
}
