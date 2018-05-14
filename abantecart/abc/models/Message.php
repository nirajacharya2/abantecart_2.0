<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcMessage
 * 
 * @property int $msg_id
 * @property string $title
 * @property string $message
 * @property string $status
 * @property int $viewed
 * @property int $repeated
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @package App\Models
 */
class AcMessage extends Eloquent
{
	protected $primaryKey = 'msg_id';
	public $timestamps = false;

	protected $casts = [
		'viewed' => 'int',
		'repeated' => 'int'
	];

	protected $dates = [
		'date_added',
		'date_modified'
	];

	protected $fillable = [
		'title',
		'message',
		'status',
		'viewed',
		'repeated',
		'date_added',
		'date_modified'
	];
}
