<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcDatasetDefinition
 * 
 * @property int $dataset_column_id
 * @property int $dataset_id
 * @property string $dataset_column_name
 * @property string $dataset_column_type
 * @property int $dataset_column_sort_order
 * 
 * @property \App\Models\AcDataset $ac_dataset
 * @property \Illuminate\Database\Eloquent\Collection $ac_dataset_column_properties
 * @property \Illuminate\Database\Eloquent\Collection $ac_dataset_values
 *
 * @package App\Models
 */
class AcDatasetDefinition extends Eloquent
{
	protected $table = 'ac_dataset_definition';
	protected $primaryKey = 'dataset_column_id';
	public $timestamps = false;

	protected $casts = [
		'dataset_id' => 'int',
		'dataset_column_sort_order' => 'int'
	];

	protected $fillable = [
		'dataset_id',
		'dataset_column_name',
		'dataset_column_type',
		'dataset_column_sort_order'
	];

	public function ac_dataset()
	{
		return $this->belongsTo(\App\Models\AcDataset::class, 'dataset_id');
	}

	public function ac_dataset_column_properties()
	{
		return $this->hasMany(\App\Models\AcDatasetColumnProperty::class, 'dataset_column_id');
	}

	public function ac_dataset_values()
	{
		return $this->hasMany(\App\Models\AcDatasetValue::class, 'dataset_column_id');
	}
}
