<?php

namespace abc\models\base;

use abc\models\admin\User;
use abc\models\base\Customer;
use abc\models\BaseModel;

/**
 * Class Audit
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
class Audit extends BaseModel
{
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'user_type',
        'user_id',
        'user_name',
        'event',
        'request_id',
        'session_id',
        'auditable_type',
        'auditable_name',
        'auditable_id',
        'old_value',
        'new_value',
    ];

    public function user()
    {
        return $this->morphTo();
    }


    public function auditable() {
        return $this->morphTo();
    }


}
