<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcProductTag
 * 
 * @property int $product_id
 * @property string $tag
 * @property int $language_id
 * 
 * @property \App\Models\AcProduct $ac_product
 * @property \App\Models\AcLanguage $ac_language
 *
 * @package App\Models
 */
class AcProductTag extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'product_id' => 'int',
		'language_id' => 'int'
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
