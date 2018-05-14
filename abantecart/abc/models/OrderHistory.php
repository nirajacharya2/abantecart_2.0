<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcOrderHistory
 * 
 * @property int $order_history_id
 * @property int $order_id
 * @property int $order_status_id
 * @property int $notify
 * @property string $comment
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 * 
 * @property \App\Models\AcOrderStatus $ac_order_status
 *
 * @package App\Models
 */
class AcOrderHistory extends Eloquent
{
	protected $table = 'ac_order_history';
	protected $primaryKey = 'order_history_id';
	public $timestamps = false;

	protected $casts = [
		'order_id' => 'int',
		'order_status_id' => 'int',
		'notify' => 'int'
	];

	protected $dates = [
		'date_added',
		'date_modified'
	];

	protected $fillable = [
		'order_id',
		'order_status_id',
		'notify',
		'comment',
		'date_added',
		'date_modified'
	];

	public function ac_order_status()
	{
		return $this->belongsTo(\App\Models\AcOrderStatus::class, 'order_status_id');
	}
}
