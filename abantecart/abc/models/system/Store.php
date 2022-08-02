<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2022 Belavier Commerce LLC
 *
 * This source file is subject to Open Software License (OSL 3.0)
 * License details is bundled with this package in the file LICENSE.txt.
 * It is also available at this URL:
 * <http://www.opensource.org/licenses/OSL-3.0>
 *
 * UPGRADE NOTE:
 * Do not edit or add to this file if you wish to upgrade AbanteCart to newer
 * versions in the future. If you wish to customize AbanteCart for your
 * needs please refer to http://www.abantecart.com for more information.
 */

namespace abc\models\system;

use abc\core\engine\Registry;
use abc\models\BaseModel;
use abc\models\catalog\CategoriesToStore;
use abc\models\catalog\ManufacturersToStore;
use abc\models\content\ContentsToStore;
use abc\models\customer\Customer;
use abc\models\order\Order;
use abc\models\user\UserNotification;
use Dyrynda\Database\Support\CascadeSoftDeletes;
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

    public static function isDefaultStore(){
        $store_settings = Setting::getStoreSettings((int)Registry::session()->data['current_store_id']);
        return (Registry::config()->get('config_url') == $store_settings->config_url);
    }
}
