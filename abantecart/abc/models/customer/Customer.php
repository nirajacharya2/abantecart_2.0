<?php

namespace abc\models\customer;

use abc\models\BaseModel;
use abc\models\order\Order;
use abc\models\system\Audit;
use abc\models\system\Store;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletes;

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
class Customer extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['addresses', 'notifications', 'transactions'];

    /**
     * @var string
     */
    protected $primaryKey = 'customer_id';
    /**
     * @var bool
     */
    public $timestamps = false;

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

    protected $guarded = [
        'date_added',
        'date_modified',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function addresses()
    {
        return $this->hasMany(Address::class, 'customer_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notifications()
    {
        return $this->hasMany(CustomerNotification::class, 'customer_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions()
    {
        return $this->hasMany(CustomerTransaction::class, 'customer_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function audits()
    {
        return $this->morphMany(Audit::class, 'user');
    }

    /**
     * @throws \Exception
     */
    public function approve()
    {
        if (!$this->hasPermission('write', ['approved'])) {
            throw new \Exception('Permissions are restricted '.__CLASS__."::".__METHOD__."\n");
        }
        $this->approved = 1;
        $this->save();
    }
}
