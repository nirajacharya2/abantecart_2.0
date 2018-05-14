<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcProductOptionValueDescription
 * 
 * @property int $product_option_value_id
 * @property int $language_id
 * @property int $product_id
 * @property string $name
 * @property string $grouped_attribute_names
 * 
 * @property \App\Models\AcProduct $ac_product
 * @property \App\Models\AcLanguage $ac_language
 *
 * @package App\Models
 */
class AcProductOptionValueDescription extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'product_option_value_id' => 'int',
		'language_id' => 'int',
		'product_id' => 'int'
	];

	protected $fillable = [
		'product_id',
		'name',
		'grouped_attribute_names'
	];

	public function ac_product()
	{
		return $this->belongsTo(\App\Models\AcProduct::class, 'product_id');
	}

	public function ac_language()
	{
		return $this->belongsTo(\App\Models\AcLanguage::class, 'language_id');
	}
}
