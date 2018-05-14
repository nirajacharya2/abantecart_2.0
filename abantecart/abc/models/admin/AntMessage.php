<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcAntMessage
 * 
 * @property string $id
 * @property int $priority
 * @property \Carbon\Carbon $start_date
 * @property \Carbon\Carbon $end_date
 * @property \Carbon\Carbon $viewed_date
 * @property int $viewed
 * @property string $title
 * @property string $description
 * @property string $html
 * @property string $url
 * @property string $language_code
 * @property \Carbon\Carbon $date_modified
 *
 * @package App\Models
 */
class AcAntMessage extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'priority' => 'int',
		'viewed' => 'int'
	];

	protected $dates = [
		'start_date',
		'end_date',
		'viewed_date',
		'date_modified'
	];

	protected $fillable = [
		'priority',
		'start_date',
		'end_date',
		'viewed_date',
		'viewed',
		'title',
		'description',
		'html',
		'url',
		'date_modified'
	];
}
