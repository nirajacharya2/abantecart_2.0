<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcBlockLayout
 * 
 * @property int $instance_id
 * @property int $layout_id
 * @property int $block_id
 * @property int $custom_block_id
 * @property int $parent_instance_id
 * @property int $position
 * @property int $status
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @package App\Models
 */
class AcBlockLayout extends Eloquent
{
	protected $primaryKey = 'instance_id';
	public $timestamps = false;

	protected $casts = [
		'layout_id' => 'int',
		'block_id' => 'int',
		'custom_block_id' => 'int',
		'parent_instance_id' => 'int',
		'position' => 'int',
		'status' => 'int'
	];

	protected $dates = [
		'date_added',
		'date_modified'
	];

	protected $fillable = [
		'layout_id',
		'block_id',
		'custom_block_id',
		'parent_instance_id',
		'position',
		'status',
		'date_added',
		'date_modified'
	];
}
