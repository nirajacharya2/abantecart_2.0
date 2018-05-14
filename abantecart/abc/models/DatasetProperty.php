<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcDatasetProperty
 * 
 * @property int $rowid
 * @property int $dataset_id
 * @property string $dataset_property_name
 * @property string $dataset_property_value
 * 
 * @property \App\Models\AcDataset $ac_dataset
 *
 * @package App\Models
 */
class AcDatasetProperty extends Eloquent
{
	protected $primaryKey = 'rowid';
	public $timestamps = false;

	protected $casts = [
		'dataset_id' => 'int'
	];

	protected $fillable = [
		'dataset_id',
		'dataset_property_name',
		'dataset_property_value'
	];

	public function ac_dataset()
	{
		return $this->belongsTo(\App\Models\AcDataset::class, 'dataset_id');
	}
}
