<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcFieldValue
 * 
 * @property int $value_id
 * @property int $field_id
 * @property string $value
 * @property int $language_id
 * 
 * @property \App\Models\AcField $ac_field
 *
 * @package App\Models
 */
class AcFieldValue extends Eloquent
{
	protected $primaryKey = 'value_id';
	public $timestamps = false;

	protected $casts = [
		'field_id' => 'int',
		'language_id' => 'int'
	];

	protected $fillable = [
		'field_id',
		'value',
		'language_id'
	];

	public function ac_field()
	{
		return $this->belongsTo(\App\Models\AcField::class, 'field_id');
	}
}
