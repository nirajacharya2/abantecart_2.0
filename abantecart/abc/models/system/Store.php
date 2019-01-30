<?php

namespace abc\models\system;

use abc\models\BaseModel;
use abc\models\UserNotification;

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

    public function categories_to_stores()
    {
        return $this->hasMany(CategoriesToStore::class, 'store_id');
    }

    public function contents_to_stores()
    {
        return $this->hasMany(ContentsToStore::class, 'store_id');
    }

    public function customers()
    {
        return $this->hasMany(Customer::class, 'store_id');
    }

    public function manufacturers_to_stores()
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

    public function store_descriptions()
    {
        return $this->hasMany(StoreDescription::class, 'store_id');
    }

    public function user_notifications()
    {
        return $this->hasMany(UserNotification::class, 'store_id');
    }
}
