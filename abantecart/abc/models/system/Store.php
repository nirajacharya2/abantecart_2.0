<?php

namespace abc\models\system;

use abc\models\BaseModel;
use abc\models\catalog\CategoriesToStore;
use abc\models\catalog\ManufacturersToStore;
use abc\models\content\ContentsToStore;
use abc\models\customer\Customer;
use abc\models\order\Order;
use abc\models\user\UserNotification;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Store
 *
 * @property int $store_id
 * @property string $name
 * @property string $alias
 * @property int $status
 *
 *
 * @package abc\models
 */
class Store extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = [
        'descriptions',
        'categories',
        'contents',
        'customers',
        'manufacturers',
        'orders',
        'settings',
        'user_notifications',
    ];
    protected $primaryKey = 'store_id';
    public $timestamps = false;

    protected $casts = [
        'status' => 'int',
    ];

    protected $fillable = [
        'name',
        'alias',
        'status',
    ];

    public function categories()
    {
        return $this->hasMany(CategoriesToStore::class, 'store_id');
    }

    public function contents()
    {
        return $this->hasMany(ContentsToStore::class, 'store_id');
    }

    public function customers()
    {
        return $this->hasMany(Customer::class, 'store_id');
    }

    public function manufacturers()
    {
        return $this->hasMany(ManufacturersToStore::class, 'store_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'store_id');
    }

    public function settings()
    {
        return $this->hasMany(Setting::class, 'store_id');
    }

    public function descriptions()
    {
        return $this->hasMany(StoreDescription::class, 'store_id');
    }

    public function user_notifications()
    {
        return $this->hasMany(UserNotification::class, 'store_id');
    }
}
