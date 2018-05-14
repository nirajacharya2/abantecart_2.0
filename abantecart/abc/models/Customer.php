<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcCustomer
 * 
 * @property int $customer_id
 * @property int $store_id
 * @property string $firstname
 * @property string $lastname
 * @property string $loginname
 * @property string $email
 * @property string $telephone
 * @property string $fax
 * @property string $sms
 * @property string $salt
 * @property string $password
 * @property string $cart
 * @property string $wishlist
 * @property int $newsletter
 * @property int $address_id
 * @property int $status
 * @property int $approved
 * @property int $customer_group_id
 * @property string $ip
 * @property string $data
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 * @property \Carbon\Carbon $last_login
 * 
 * @property \App\Models\AcStore $ac_store
 * @property \Illuminate\Database\Eloquent\Collection $ac_addresses
 * @property \Illuminate\Database\Eloquent\Collection $ac_customer_notifications
 * @property \Illuminate\Database\Eloquent\Collection $ac_customer_transactions
 * @property \Illuminate\Database\Eloquent\Collection $ac_orders
 *
 * @package App\Models
 */
class AcCustomer extends Eloquent
{
	protected $primaryKey = 'customer_id';
	public $timestamps = false;

	protected $casts = [
		'store_id' => 'int',
		'newsletter' => 'int',
		'address_id' => 'int',
		'status' => 'int',
		'approved' => 'int',
		'customer_group_id' => 'int'
	];

	protected $dates = [
		'date_added',
		'date_modified',
		'last_login'
	];

	protected $hidden = [
		'password'
	];

	protected $fillable = [
		'store_id',
		'firstname',
		'lastname',
		'loginname',
		'email',
		'telephone',
		'fax',
		'sms',
		'salt',
		'password',
		'cart',
		'wishlist',
		'newsletter',
		'address_id',
		'status',
		'approved',
		'customer_group_id',
		'ip',
		'data',
		'date_added',
		'date_modified',
		'last_login'
	];

	public function ac_store()
	{
		return $this->belongsTo(\App\Models\AcStore::class, 'store_id');
	}

	public function ac_addresses()
	{
		return $this->hasMany(\App\Models\AcAddress::class, 'customer_id');
	}

	public function ac_customer_notifications()
	{
		return $this->hasMany(\App\Models\AcCustomerNotification::class, 'customer_id');
	}

	public function ac_customer_transactions()
	{
		return $this->hasMany(\App\Models\AcCustomerTransaction::class, 'customer_id');
	}

	public function ac_orders()
	{
		return $this->hasMany(\App\Models\AcOrder::class, 'customer_id');
	}
}
