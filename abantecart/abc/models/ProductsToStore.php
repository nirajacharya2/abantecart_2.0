<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcProductsToStore
 * 
 * @property int $product_id
 * @property int $store_id
 * 
 * @property \App\Models\AcProduct $ac_product
 * @property \App\Models\AcStore $ac_store
 *
 * @package App\Models
 */
class AcProductsToStore extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'product_id' => 'int',
		'store_id' => 'int'
	];

	public function ac_product()
	{
		return $this->belongsTo(\App\Models\AcProduct::class, 'product_id');
	}

	public function ac_store()
	{
		return $this->belongsTo(\App\Models\AcStore::class, 'store_id');
	}
}
