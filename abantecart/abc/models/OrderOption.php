<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcOrderOption
 * 
 * @property int $order_option_id
 * @property int $order_id
 * @property int $order_product_id
 * @property int $product_option_value_id
 * @property string $name
 * @property string $sku
 * @property string $value
 * @property float $price
 * @property string $prefix
 * @property string $settings
 * 
 * @property \App\Models\AcProductOptionValue $ac_product_option_value
 *
 * @package App\Models
 */
class AcOrderOption extends Eloquent
{
	protected $primaryKey = 'order_option_id';
	public $timestamps = false;

	protected $casts = [
		'order_id' => 'int',
		'order_product_id' => 'int',
		'product_option_value_id' => 'int',
		'price' => 'float'
	];

	protected $fillable = [
		'order_id',
		'order_product_id',
		'product_option_value_id',
		'name',
		'sku',
		'value',
		'price',
		'prefix',
		'settings'
	];

	public function ac_product_option_value()
	{
		return $this->belongsTo(\App\Models\AcProductOptionValue::class, 'product_option_value_id');
	}
}
