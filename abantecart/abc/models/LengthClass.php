<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcLengthClass
 * 
 * @property int $length_class_id
 * @property float $value
 * @property string $iso_code
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @package App\Models
 */
class AcLengthClass extends Eloquent
{
	public $timestamps = false;

	protected $casts = [
		'value' => 'float'
	];

	protected $dates = [
		'date_added',
		'date_modified'
	];

	protected $fillable = [
		'value',
		'date_added',
		'date_modified'
	];
}
