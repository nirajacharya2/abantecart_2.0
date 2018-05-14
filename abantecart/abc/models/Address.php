<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcAddress
 * 
 * @property int $address_id
 * @property int $customer_id
 * @property string $company
 * @property string $firstname
 * @property string $lastname
 * @property string $address_1
 * @property string $address_2
 * @property string $postcode
 * @property string $city
 * @property int $country_id
 * @property int $zone_id
 * 
 * @property \App\Models\AcCustomer $ac_customer
 * @property \App\Models\AcCountry $ac_country
 * @property \App\Models\AcZone $ac_zone
 *
 * @package App\Models
 */
class AcAddress extends Eloquent
{
	protected $primaryKey = 'address_id';
	public $timestamps = false;

	protected $casts = [
		'customer_id' => 'int',
		'country_id' => 'int',
		'zone_id' => 'int'
	];

	protected $fillable = [
		'customer_id',
		'company',
		'firstname',
		'lastname',
		'address_1',
		'address_2',
		'postcode',
		'city',
		'country_id',
		'zone_id'
	];

	public function ac_customer()
	{
		return $this->belongsTo(\App\Models\AcCustomer::class, 'customer_id');
	}

	public function ac_country()
	{
		return $this->belongsTo(\App\Models\AcCountry::class, 'country_id');
	}

	public function ac_zone()
	{
		return $this->belongsTo(\App\Models\AcZone::class, 'zone_id');
	}
}
