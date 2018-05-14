<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcProductFilterRangesDescription
 * 
 * @property int $range_id
 * @property string $name
 * @property int $language_id
 *
 * @package App\Models
 */
class AcProductFilterRangesDescription extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'range_id' => 'int',
		'language_id' => 'int'
	];

	protected $fillable = [
		'name'
	];
}
