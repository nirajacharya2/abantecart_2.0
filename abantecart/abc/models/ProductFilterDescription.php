<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcProductFilterDescription
 * 
 * @property int $filter_id
 * @property string $value
 * @property int $language_id
 *
 * @package App\Models
 */
class AcProductFilterDescription extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'filter_id' => 'int',
		'language_id' => 'int'
	];

	protected $fillable = [
		'value'
	];
}
