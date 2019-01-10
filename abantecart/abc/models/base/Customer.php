<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class Customer
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
 * @property Store $store
 * @property \Illuminate\Database\Eloquent\Collection $addresses
 * @property \Illuminate\Database\Eloquent\Collection $customer_notifications
 * @property \Illuminate\Database\Eloquent\Collection $customer_transactions
 * @property \Illuminate\Database\Eloquent\Collection $orders
 *
 * @package abc\models
 */
class Customer extends AModelBase
{
    /**
     * @var string
     */
    protected $primaryKey = 'customer_id';
    /**
     * @var bool
     */
    public $timestamps = false;

    protected $permissions = [
        self::CLI => ['update', 'delete'],
        self::ADMIN => ['update', 'delete'],
        self::CUSTOMER => ['update', 'save']
    ];

    protected $casts = [
        'store_id'          => 'int',
        'newsletter'        => 'int',
        'address_id'        => 'int',
        'status'            => 'int',
        'approved'          => 'int',
        'customer_group_id' => 'int',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
        'last_login',
    ];

    protected $hidden = [
        'password',
    ];

//    protected $fillable = [
//        'store_id',
//        'firstname',
//        'lastname',
//        'loginname',
//        'email',
//        'telephone',
//        'fax',
//        'sms',
//        'salt',
//        'password',
//        'cart',
//        'wishlist',
//        'newsletter',
//        'address_id',
//        'status',
//        'approved',
//        'customer_group_id',
//        'ip',
//        'data',
//        'date_added',
//        'date_modified',
//        'last_login',
//    ];


    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function addresses()
    {
        return $this->hasMany(Address::class, 'customer_id');
    }

    public function customer_notifications()
    {
        return $this->hasMany(CustomerNotification::class, 'customer_id');
    }

    public function customer_transactions()
    {
        return $this->hasMany(CustomerTransaction::class, 'customer_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id');
    }
}
