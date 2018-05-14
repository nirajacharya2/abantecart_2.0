<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcProductSpecial
 * 
 * @property int $product_special_id
 * @property int $product_id
 * @property int $customer_group_id
 * @property int $priority
 * @property float $price
 * @property \Carbon\Carbon $date_start
 * @property \Carbon\Carbon $date_end
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 * 
 * @property \App\Models\AcProduct $ac_product
 *
 * @package App\Models
 */
class AcProductSpecial extends Eloquent
{
	protected $primaryKey = 'product_special_id';
	public $timestamps = false;

	protected $casts = [
		'product_id' => 'int',
		'customer_group_id' => 'int',
		'priority' => 'int',
		'price' => 'float'
	];

	protected $dates = [
		'date_start',
		'date_end',
		'date_added',
		'date_modified'
	];

	protected $fillable = [
		'product_id',
		'customer_group_id',
		'priority',
		'price',
		'date_start',
		'date_end',
		'date_added',
		'date_modified'
	];

	public function ac_product()
	{
		return $this->belongsTo(\App\Models\AcProduct::class, 'product_id');
	}
}
