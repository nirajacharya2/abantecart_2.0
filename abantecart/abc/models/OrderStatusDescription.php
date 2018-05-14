<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcOrderStatusDescription
 * 
 * @property int $order_status_id
 * @property int $language_id
 * @property string $name
 * 
 * @property \App\Models\AcOrderStatus $ac_order_status
 * @property \App\Models\AcLanguage $ac_language
 *
 * @package App\Models
 */
class AcOrderStatusDescription extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'order_status_id' => 'int',
		'language_id' => 'int'
	];

	protected $fillable = [
		'name'
	];

	public function ac_order_status()
	{
		return $this->belongsTo(\App\Models\AcOrderStatus::class, 'order_status_id');
	}

	public function ac_language()
	{
		return $this->belongsTo(\App\Models\AcLanguage::class, 'language_id');
	}
}
