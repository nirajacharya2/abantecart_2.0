<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcOrderDataType
 * 
 * @property int $type_id
 * @property int $language_id
 * @property string $name
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 * 
 * @property \App\Models\AcLanguage $ac_language
 * @property \Illuminate\Database\Eloquent\Collection $ac_order_data
 *
 * @package App\Models
 */
class AcOrderDataType extends Eloquent
{
	protected $primaryKey = 'type_id';
	public $timestamps = false;

	protected $casts = [
		'language_id' => 'int'
	];

	protected $dates = [
		'date_added',
		'date_modified'
	];

	protected $fillable = [
		'language_id',
		'name',
		'date_added',
		'date_modified'
	];

	public function ac_language()
	{
		return $this->belongsTo(\App\Models\AcLanguage::class, 'language_id');
	}

	public function ac_order_data()
	{
		return $this->hasMany(\App\Models\AcOrderDatum::class, 'type_id');
	}
}
