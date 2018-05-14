<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcSetting
 * 
 * @property int $setting_id
 * @property int $store_id
 * @property string $group
 * @property string $key
 * @property string $value
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 * 
 * @property \App\Models\AcStore $ac_store
 *
 * @package App\Models
 */
class AcSetting extends Eloquent
{
	public $timestamps = false;

	protected $casts = [
		'store_id' => 'int'
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

	public function ac_store()
	{
		return $this->belongsTo(\App\Models\AcStore::class, 'store_id');
	}
}
