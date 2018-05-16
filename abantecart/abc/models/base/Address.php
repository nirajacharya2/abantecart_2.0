<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class Address
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
 * @property Customer $customer
 * @property Country $country
 * @property Zone $zone
 *
 * @package abc\models
 */
class Address extends AModelBase
{
    protected $primaryKey = 'address_id';
    public $timestamps = false;

    protected $casts = [
        'customer_id' => 'int',
        'country_id'  => 'int',
        'zone_id'     => 'int',
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
        'zone_id',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }
}
