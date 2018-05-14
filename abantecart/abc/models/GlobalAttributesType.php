<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcGlobalAttributesType
 * 
 * @property int $attribute_type_id
 * @property string $type_key
 * @property string $controller
 * @property int $sort_order
 * @property int $status
 *
 * @package App\Models
 */
class AcGlobalAttributesType extends Eloquent
{
	protected $primaryKey = 'attribute_type_id';
	public $timestamps = false;

	protected $casts = [
		'sort_order' => 'int',
		'status' => 'int'
	];

	protected $fillable = [
		'type_key',
		'controller',
		'sort_order',
		'status'
	];
}
