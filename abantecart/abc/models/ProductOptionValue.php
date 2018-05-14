<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcProductOptionValue
 * 
 * @property int $product_option_value_id
 * @property int $product_option_id
 * @property int $product_id
 * @property int $group_id
 * @property string $sku
 * @property int $quantity
 * @property int $subtract
 * @property float $price
 * @property string $prefix
 * @property float $weight
 * @property string $weight_type
 * @property int $attribute_value_id
 * @property string $grouped_attribute_data
 * @property int $sort_order
 * @property int $default
 * 
 * @property \App\Models\AcProductOption $ac_product_option
 * @property \App\Models\AcProduct $ac_product
 * @property \Illuminate\Database\Eloquent\Collection $ac_order_options
 *
 * @package App\Models
 */
class AcProductOptionValue extends Eloquent
{
	protected $primaryKey = 'product_option_value_id';
	public $timestamps = false;

	protected $casts = [
		'product_option_id' => 'int',
		'product_id' => 'int',
		'group_id' => 'int',
		'quantity' => 'int',
		'subtract' => 'int',
		'price' => 'float',
		'weight' => 'float',
		'attribute_value_id' => 'int',
		'sort_order' => 'int',
		'default' => 'int'
	];

	protected $fillable = [
		'product_option_id',
		'product_id',
		'group_id',
		'sku',
		'quantity',
		'subtract',
		'price',
		'prefix',
		'weight',
		'weight_type',
		'attribute_value_id',
		'grouped_attribute_data',
		'sort_order',
		'default'
	];

	public function ac_product_option()
	{
		return $this->belongsTo(\App\Models\AcProductOption::class, 'product_option_id');
	}

	public function ac_product()
	{
		return $this->belongsTo(\App\Models\AcProduct::class, 'product_id');
	}

	public function ac_order_options()
	{
		return $this->hasMany(\App\Models\AcOrderOption::class, 'product_option_value_id');
	}
}
