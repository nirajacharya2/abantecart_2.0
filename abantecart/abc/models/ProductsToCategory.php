<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcProductsToCategory
 * 
 * @property int $product_id
 * @property int $category_id
 * 
 * @property \App\Models\AcProduct $ac_product
 * @property \App\Models\AcCategory $ac_category
 *
 * @package App\Models
 */
class AcProductsToCategory extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'product_id' => 'int',
		'category_id' => 'int'
	];

	public function ac_product()
	{
		return $this->belongsTo(\App\Models\AcProduct::class, 'product_id');
	}

	public function ac_category()
	{
		return $this->belongsTo(\App\Models\AcCategory::class, 'category_id');
	}
}
