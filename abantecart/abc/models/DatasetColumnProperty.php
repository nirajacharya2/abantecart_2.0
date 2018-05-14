<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcDatasetColumnProperty
 * 
 * @property int $rowid
 * @property int $dataset_column_id
 * @property string $dataset_column_property_name
 * @property string $dataset_column_property_value
 * 
 * @property \App\Models\AcDatasetDefinition $ac_dataset_definition
 *
 * @package App\Models
 */
class AcDatasetColumnProperty extends Eloquent
{
	protected $primaryKey = 'rowid';
	public $timestamps = false;

	protected $casts = [
		'dataset_column_id' => 'int'
	];

	protected $fillable = [
		'dataset_column_id',
		'dataset_column_property_name',
		'dataset_column_property_value'
	];

	public function ac_dataset_definition()
	{
		return $this->belongsTo(\App\Models\AcDatasetDefinition::class, 'dataset_column_id');
	}
}
