<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcOrderProduct
 * 
 * @property int $order_product_id
 * @property int $order_id
 * @property int $product_id
 * @property string $name
 * @property string $model
 * @property string $sku
 * @property float $price
 * @property float $total
 * @property float $tax
 * @property int $quantity
 * @property int $subtract
 * 
 * @property \App\Models\AcOrder $ac_order
 * @property \App\Models\AcProduct $ac_product
 * @property \Illuminate\Database\Eloquent\Collection $ac_order_downloads
 * @property \Illuminate\Database\Eloquent\Collection $ac_order_downloads_histories
 *
 * @package App\Models
 */
class AcOrderProduct extends Eloquent
{
	protected $primaryKey = 'order_product_id';
	public $timestamps = false;

	protected $casts = [
		'order_id' => 'int',
		'product_id' => 'int',
		'price' => 'float',
		'total' => 'float',
		'tax' => 'float',
		'quantity' => 'int',
		'subtract' => 'int'
	];

	protected $fillable = [
		'order_id',
		'product_id',
		'name',
		'model',
		'sku',
		'price',
		'total',
		'tax',
		'quantity',
		'subtract'
	];

	public function ac_order()
	{
		return $this->belongsTo(\App\Models\AcOrder::class, 'order_id');
	}

	public function ac_product()
	{
		return $this->belongsTo(\App\Models\AcProduct::class, 'product_id');
	}

	public function ac_order_downloads()
	{
		return $this->hasMany(\App\Models\AcOrderDownload::class, 'order_product_id');
	}

	public function ac_order_downloads_histories()
	{
		return $this->hasMany(\App\Models\AcOrderDownloadsHistory::class, 'order_product_id');
	}
}
