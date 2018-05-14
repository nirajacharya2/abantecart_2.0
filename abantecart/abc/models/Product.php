<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcProduct
 * 
 * @property int $product_id
 * @property string $model
 * @property string $sku
 * @property string $location
 * @property int $quantity
 * @property string $stock_checkout
 * @property int $stock_status_id
 * @property int $manufacturer_id
 * @property int $shipping
 * @property int $ship_individually
 * @property int $free_shipping
 * @property float $shipping_price
 * @property float $price
 * @property int $tax_class_id
 * @property \Carbon\Carbon $date_available
 * @property float $weight
 * @property int $weight_class_id
 * @property float $length
 * @property float $width
 * @property float $height
 * @property int $length_class_id
 * @property int $status
 * @property int $viewed
 * @property int $sort_order
 * @property int $subtract
 * @property int $minimum
 * @property int $maximum
 * @property float $cost
 * @property int $call_to_order
 * @property string $settings
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 * 
 * @property \Illuminate\Database\Eloquent\Collection $ac_coupons_products
 * @property \Illuminate\Database\Eloquent\Collection $ac_order_products
 * @property \Illuminate\Database\Eloquent\Collection $ac_product_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_product_discounts
 * @property \Illuminate\Database\Eloquent\Collection $ac_product_option_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_product_option_value_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_product_option_values
 * @property \Illuminate\Database\Eloquent\Collection $ac_product_options
 * @property \Illuminate\Database\Eloquent\Collection $ac_product_specials
 * @property \Illuminate\Database\Eloquent\Collection $ac_product_tags
 * @property \App\Models\AcProductsFeatured $ac_products_featured
 * @property \Illuminate\Database\Eloquent\Collection $ac_products_relateds
 * @property \Illuminate\Database\Eloquent\Collection $ac_products_to_categories
 * @property \Illuminate\Database\Eloquent\Collection $ac_products_to_downloads
 * @property \Illuminate\Database\Eloquent\Collection $ac_products_to_stores
 * @property \Illuminate\Database\Eloquent\Collection $ac_reviews
 *
 * @package App\Models
 */
class AcProduct extends Eloquent
{
	protected $primaryKey = 'product_id';
	public $timestamps = false;

	protected $casts = [
		'quantity' => 'int',
		'stock_status_id' => 'int',
		'manufacturer_id' => 'int',
		'shipping' => 'int',
		'ship_individually' => 'int',
		'free_shipping' => 'int',
		'shipping_price' => 'float',
		'price' => 'float',
		'tax_class_id' => 'int',
		'weight' => 'float',
		'weight_class_id' => 'int',
		'length' => 'float',
		'width' => 'float',
		'height' => 'float',
		'length_class_id' => 'int',
		'status' => 'int',
		'viewed' => 'int',
		'sort_order' => 'int',
		'subtract' => 'int',
		'minimum' => 'int',
		'maximum' => 'int',
		'cost' => 'float',
		'call_to_order' => 'int'
	];

	protected $dates = [
		'date_available',
		'date_added',
		'date_modified'
	];

	protected $fillable = [
		'model',
		'sku',
		'location',
		'quantity',
		'stock_checkout',
		'stock_status_id',
		'manufacturer_id',
		'shipping',
		'ship_individually',
		'free_shipping',
		'shipping_price',
		'price',
		'tax_class_id',
		'date_available',
		'weight',
		'weight_class_id',
		'length',
		'width',
		'height',
		'length_class_id',
		'status',
		'viewed',
		'sort_order',
		'subtract',
		'minimum',
		'maximum',
		'cost',
		'call_to_order',
		'settings',
		'date_added',
		'date_modified'
	];

	public function ac_coupons_products()
	{
		return $this->hasMany(\App\Models\AcCouponsProduct::class, 'product_id');
	}

	public function ac_order_products()
	{
		return $this->hasMany(\App\Models\AcOrderProduct::class, 'product_id');
	}

	public function ac_product_descriptions()
	{
		return $this->hasMany(\App\Models\AcProductDescription::class, 'product_id');
	}

	public function ac_product_discounts()
	{
		return $this->hasMany(\App\Models\AcProductDiscount::class, 'product_id');
	}

	public function ac_product_option_descriptions()
	{
		return $this->hasMany(\App\Models\AcProductOptionDescription::class, 'product_id');
	}

	public function ac_product_option_value_descriptions()
	{
		return $this->hasMany(\App\Models\AcProductOptionValueDescription::class, 'product_id');
	}

	public function ac_product_option_values()
	{
		return $this->hasMany(\App\Models\AcProductOptionValue::class, 'product_id');
	}

	public function ac_product_options()
	{
		return $this->hasMany(\App\Models\AcProductOption::class, 'product_id');
	}

	public function ac_product_specials()
	{
		return $this->hasMany(\App\Models\AcProductSpecial::class, 'product_id');
	}

	public function ac_product_tags()
	{
		return $this->hasMany(\App\Models\AcProductTag::class, 'product_id');
	}

	public function ac_products_featured()
	{
		return $this->hasOne(\App\Models\AcProductsFeatured::class, 'product_id');
	}

	public function ac_products_relateds()
	{
		return $this->hasMany(\App\Models\AcProductsRelated::class, 'product_id');
	}

	public function ac_products_to_categories()
	{
		return $this->hasMany(\App\Models\AcProductsToCategory::class, 'product_id');
	}

	public function ac_products_to_downloads()
	{
		return $this->hasMany(\App\Models\AcProductsToDownload::class, 'product_id');
	}

	public function ac_products_to_stores()
	{
		return $this->hasMany(\App\Models\AcProductsToStore::class, 'product_id');
	}

	public function ac_reviews()
	{
		return $this->hasMany(\App\Models\AcReview::class, 'product_id');
	}
}
