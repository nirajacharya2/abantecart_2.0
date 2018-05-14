<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcResourceType
 * 
 * @property int $type_id
 * @property string $type_name
 * @property string $default_directory
 * @property string $default_icon
 * @property string $file_types
 * @property bool $access_type
 * 
 * @property \Illuminate\Database\Eloquent\Collection $ac_resource_libraries
 *
 * @package App\Models
 */
class AcResourceType extends Eloquent
{
	protected $primaryKey = 'type_id';
	public $timestamps = false;

	protected $casts = [
		'access_type' => 'bool'
	];

	protected $fillable = [
		'type_name',
		'default_directory',
		'default_icon',
		'file_types',
		'access_type'
	];

	public function ac_resource_libraries()
	{
		return $this->hasMany(\App\Models\AcResourceLibrary::class, 'type_id');
	}
}
