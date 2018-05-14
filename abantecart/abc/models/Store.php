<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class AcStore
 *
 * @property int                                      $store_id
 * @property string                                   $name
 * @property string                                   $alias
 * @property int                                      $status
 *
 * @property \Illuminate\Database\Eloquent\Collection $categories_to_stores
 * @property \Illuminate\Database\Eloquent\Collection $contents_to_stores
 * @property \Illuminate\Database\Eloquent\Collection $customers
 * @property \Illuminate\Database\Eloquent\Collection $manufacturers_to_stores
 * @property \Illuminate\Database\Eloquent\Collection $orders
 * @property \Illuminate\Database\Eloquent\Collection $products_to_stores
 * @property \Illuminate\Database\Eloquent\Collection $settings
 * @property \Illuminate\Database\Eloquent\Collection $store_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $user_notifications
 *
 * @package abc\models
 */
class Store extends AModelBase
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
        return $this->hasMany(\abc\models\Order::class, 'store_id');
    }

    public function products_to_stores()
    {
        return $this->hasMany(ProductsToStore::class, 'store_id');
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
