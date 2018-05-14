<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcGlobalAttributesTypeDescription
 * 
 * @property int $attribute_type_id
 * @property int $language_id
 * @property string $type_name
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @package App\Models
 */
class AcGlobalAttributesTypeDescription extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'attribute_type_id' => 'int',
		'language_id' => 'int'
	];

	protected $dates = [
		'date_added',
		'date_modified'
	];

	protected $fillable = [
		'type_name',
		'date_added',
		'date_modified'
	];
}
