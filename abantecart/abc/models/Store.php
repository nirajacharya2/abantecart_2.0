<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcStore
 * 
 * @property int $store_id
 * @property string $name
 * @property string $alias
 * @property int $status
 * 
 * @property \Illuminate\Database\Eloquent\Collection $ac_categories_to_stores
 * @property \Illuminate\Database\Eloquent\Collection $ac_contents_to_stores
 * @property \Illuminate\Database\Eloquent\Collection $ac_customers
 * @property \Illuminate\Database\Eloquent\Collection $ac_manufacturers_to_stores
 * @property \Illuminate\Database\Eloquent\Collection $ac_orders
 * @property \Illuminate\Database\Eloquent\Collection $ac_products_to_stores
 * @property \Illuminate\Database\Eloquent\Collection $ac_settings
 * @property \Illuminate\Database\Eloquent\Collection $ac_store_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_user_notifications
 *
 * @package App\Models
 */
class AcStore extends Eloquent
{
	protected $primaryKey = 'store_id';
	public $timestamps = false;

	protected $casts = [
		'status' => 'int'
	];

	protected $fillable = [
		'name',
		'alias',
		'status'
	];

	public function ac_categories_to_stores()
	{
		return $this->hasMany(\App\Models\AcCategoriesToStore::class, 'store_id');
	}

	public function ac_contents_to_stores()
	{
		return $this->hasMany(\App\Models\AcContentsToStore::class, 'store_id');
	}

	public function ac_customers()
	{
		return $this->hasMany(\App\Models\AcCustomer::class, 'store_id');
	}

	public function ac_manufacturers_to_stores()
	{
		return $this->hasMany(\App\Models\AcManufacturersToStore::class, 'store_id');
	}

	public function ac_orders()
	{
		return $this->hasMany(\App\Models\AcOrder::class, 'store_id');
	}

	public function ac_products_to_stores()
	{
		return $this->hasMany(\App\Models\AcProductsToStore::class, 'store_id');
	}

	public function ac_settings()
	{
		return $this->hasMany(\App\Models\AcSetting::class, 'store_id');
	}

	public function ac_store_descriptions()
	{
		return $this->hasMany(\App\Models\AcStoreDescription::class, 'store_id');
	}

	public function ac_user_notifications()
	{
		return $this->hasMany(\App\Models\AcUserNotification::class, 'store_id');
	}
}
