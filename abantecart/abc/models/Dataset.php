<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcDataset
 * 
 * @property int $dataset_id
 * @property string $dataset_name
 * @property string $dataset_key
 * 
 * @property \Illuminate\Database\Eloquent\Collection $ac_dataset_definitions
 * @property \Illuminate\Database\Eloquent\Collection $ac_dataset_properties
 *
 * @package App\Models
 */
class AcDataset extends Eloquent
{
	protected $primaryKey = 'dataset_id';
	public $timestamps = false;

	protected $fillable = [
		'dataset_name',
		'dataset_key'
	];

	public function ac_dataset_definitions()
	{
		return $this->hasMany(\App\Models\AcDatasetDefinition::class, 'dataset_id');
	}

	public function ac_dataset_properties()
	{
		return $this->hasMany(\App\Models\AcDatasetProperty::class, 'dataset_id');
	}
}
