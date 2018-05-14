<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcOrderStatus
 * 
 * @property int $order_status_id
 * @property string $status_text_id
 * 
 * @property \Illuminate\Database\Eloquent\Collection $ac_order_histories
 * @property \Illuminate\Database\Eloquent\Collection $ac_order_status_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_orders
 *
 * @package App\Models
 */
class AcOrderStatus extends Eloquent
{
	public $timestamps = false;

	public function ac_order_histories()
	{
		return $this->hasMany(\App\Models\AcOrderHistory::class, 'order_status_id');
	}

	public function ac_order_status_descriptions()
	{
		return $this->hasMany(\App\Models\AcOrderStatusDescription::class, 'order_status_id');
	}

	public function ac_orders()
	{
		return $this->hasMany(\App\Models\AcOrder::class, 'order_status_id');
	}
}
