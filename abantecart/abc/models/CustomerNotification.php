<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcCustomerNotification
 * 
 * @property int $customer_id
 * @property string $sendpoint
 * @property string $protocol
 * @property int $status
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 * 
 * @property \App\Models\AcCustomer $ac_customer
 *
 * @package App\Models
 */
class AcCustomerNotification extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'customer_id' => 'int',
		'status' => 'int'
	];

	protected $dates = [
		'date_added',
		'date_modified'
	];

	protected $fillable = [
		'status',
		'date_added',
		'date_modified'
	];

	public function ac_customer()
	{
		return $this->belongsTo(\App\Models\AcCustomer::class, 'customer_id');
	}
}
