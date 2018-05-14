<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcStockStatus
 * 
 * @property int $stock_status_id
 * @property int $language_id
 * @property string $name
 * 
 * @property \App\Models\AcLanguage $ac_language
 *
 * @package App\Models
 */
class AcStockStatus extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'stock_status_id' => 'int',
		'language_id' => 'int'
	];

	protected $fillable = [
		'name'
	];

	public function ac_language()
	{
		return $this->belongsTo(\App\Models\AcLanguage::class, 'language_id');
	}
}
