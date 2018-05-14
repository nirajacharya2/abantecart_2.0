<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcGlobalAttributesGroup
 * 
 * @property int $attribute_group_id
 * @property int $sort_order
 * @property int $status
 *
 * @package App\Models
 */
class AcGlobalAttributesGroup extends Eloquent
{
	protected $primaryKey = 'attribute_group_id';
	public $timestamps = false;

	protected $casts = [
		'sort_order' => 'int',
		'status' => 'int'
	];

	protected $fillable = [
		'sort_order',
		'status'
	];
}
