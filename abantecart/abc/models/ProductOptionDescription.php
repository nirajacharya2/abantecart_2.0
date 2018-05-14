<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcProductOptionDescription
 * 
 * @property int $product_option_id
 * @property int $language_id
 * @property int $product_id
 * @property string $name
 * @property string $option_placeholder
 * @property string $error_text
 * 
 * @property \App\Models\AcProduct $ac_product
 * @property \App\Models\AcLanguage $ac_language
 * @property \App\Models\AcProductOption $ac_product_option
 *
 * @package App\Models
 */
class AcProductOptionDescription extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'product_option_id' => 'int',
		'language_id' => 'int',
		'product_id' => 'int'
	];

	protected $fillable = [
		'product_id',
		'name',
		'option_placeholder',
		'error_text'
	];

	public function ac_product()
	{
		return $this->belongsTo(\App\Models\AcProduct::class, 'product_id');
	}

	public function ac_language()
	{
		return $this->belongsTo(\App\Models\AcLanguage::class, 'language_id');
	}

	public function ac_product_option()
	{
		return $this->belongsTo(\App\Models\AcProductOption::class, 'product_option_id');
	}
}
