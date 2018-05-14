<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcTaskDetail
 * 
 * @property int $task_id
 * @property string $created_by
 * @property string $settings
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @package App\Models
 */
class AcTaskDetail extends Eloquent
{
	protected $primaryKey = 'task_id';
	public $timestamps = false;

	protected $dates = [
		'date_added',
		'date_modified'
	];

	protected $fillable = [
		'created_by',
		'settings',
		'date_added',
		'date_modified'
	];
}
