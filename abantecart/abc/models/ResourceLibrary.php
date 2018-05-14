<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcResourceLibrary
 * 
 * @property int $resource_id
 * @property int $type_id
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 * 
 * @property \App\Models\AcResourceType $ac_resource_type
 * @property \Illuminate\Database\Eloquent\Collection $ac_resource_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_resource_maps
 *
 * @package App\Models
 */
class AcResourceLibrary extends Eloquent
{
	protected $table = 'ac_resource_library';
	protected $primaryKey = 'resource_id';
	public $timestamps = false;

	protected $casts = [
		'type_id' => 'int'
	];

	protected $dates = [
		'date_added',
		'date_modified'
	];

	protected $fillable = [
		'type_id',
		'date_added',
		'date_modified'
	];

	public function ac_resource_type()
	{
		return $this->belongsTo(\App\Models\AcResourceType::class, 'type_id');
	}

	public function ac_resource_descriptions()
	{
		return $this->hasMany(\App\Models\AcResourceDescription::class, 'resource_id');
	}

	public function ac_resource_maps()
	{
		return $this->hasMany(\App\Models\AcResourceMap::class, 'resource_id');
	}
}
