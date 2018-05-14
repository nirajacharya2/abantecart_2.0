<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcProductFilterRange
 * 
 * @property int $range_id
 * @property int $feature_id
 * @property int $filter_id
 * @property float $from
 * @property float $to
 * @property int $sort_order
 *
 * @package App\Models
 */
class AcProductFilterRange extends Eloquent
{
	protected $primaryKey = 'range_id';
	public $timestamps = false;

	protected $casts = [
		'feature_id' => 'int',
		'filter_id' => 'int',
		'from' => 'float',
		'to' => 'float',
		'sort_order' => 'int'
	];

	protected $fillable = [
		'feature_id',
		'filter_id',
		'from',
		'to',
		'sort_order'
	];
}
