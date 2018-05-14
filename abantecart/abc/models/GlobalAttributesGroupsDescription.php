<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcGlobalAttributesGroupsDescription
 * 
 * @property int $attribute_group_id
 * @property int $language_id
 * @property string $name
 *
 * @package App\Models
 */
class AcGlobalAttributesGroupsDescription extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'attribute_group_id' => 'int',
		'language_id' => 'int'
	];

	protected $fillable = [
		'name'
	];
}
