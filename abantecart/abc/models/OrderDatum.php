<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcOrderDatum
 * 
 * @property int $order_id
 * @property int $type_id
 * @property string $data
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 * 
 * @property \App\Models\AcOrder $ac_order
 * @property \App\Models\AcOrderDataType $ac_order_data_type
 *
 * @package App\Models
 */
class AcOrderDatum extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'order_id' => 'int',
		'type_id' => 'int'
	];

	protected $dates = [
		'date_added',
		'date_modified'
	];

	protected $fillable = [
		'data',
		'date_added',
		'date_modified'
	];

	public function ac_order()
	{
		return $this->belongsTo(\App\Models\AcOrder::class, 'order_id');
	}

	public function ac_order_data_type()
	{
		return $this->belongsTo(\App\Models\AcOrderDataType::class, 'type_id');
	}
}
