<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcProductFilter
 * 
 * @property int $filter_id
 * @property string $filter_type
 * @property string $categories_hash
 * @property int $feature_id
 * @property int $sort_order
 * @property int $status
 *
 * @package App\Models
 */
class AcProductFilter extends Eloquent
{
	protected $primaryKey = 'filter_id';
	public $timestamps = false;

	protected $casts = [
		'feature_id' => 'int',
		'sort_order' => 'int',
		'status' => 'int'
	];

	protected $fillable = [
		'filter_type',
		'categories_hash',
		'feature_id',
		'sort_order',
		'status'
	];
}
