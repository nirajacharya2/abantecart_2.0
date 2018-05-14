<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcOnlineCustomer
 * 
 * @property int $customer_id
 * @property string $ip
 * @property string $url
 * @property string $referer
 * @property \Carbon\Carbon $date_added
 *
 * @package App\Models
 */
class AcOnlineCustomer extends Eloquent
{
	protected $primaryKey = 'ip';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'customer_id' => 'int'
	];

	protected $dates = [
		'date_added'
	];

	protected $fillable = [
		'customer_id',
		'url',
		'referer',
		'date_added'
	];
}
