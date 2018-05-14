<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcProductsRelated
 * 
 * @property int $product_id
 * @property int $related_id
 * 
 * @property \App\Models\AcProduct $ac_product
 *
 * @package App\Models
 */
class AcProductsRelated extends Eloquent
{
	protected $table = 'ac_products_related';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'product_id' => 'int',
		'related_id' => 'int'
	];

	public function ac_product()
	{
		return $this->belongsTo(\App\Models\AcProduct::class, 'product_id');
	}
}
