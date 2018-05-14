<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcResourceMap
 * 
 * @property int $resource_id
 * @property string $object_name
 * @property int $object_id
 * @property bool $default
 * @property int $sort_order
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 * 
 * @property \App\Models\AcResourceLibrary $ac_resource_library
 *
 * @package App\Models
 */
class AcResourceMap extends Eloquent
{
	protected $table = 'ac_resource_map';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'resource_id' => 'int',
		'object_id' => 'int',
		'default' => 'bool',
		'sort_order' => 'int'
	];

	protected $dates = [
		'date_added',
		'date_modified'
	];

	protected $fillable = [
		'default',
		'sort_order',
		'date_added',
		'date_modified'
	];

	public function ac_resource_library()
	{
		return $this->belongsTo(\App\Models\AcResourceLibrary::class, 'resource_id');
	}
}
