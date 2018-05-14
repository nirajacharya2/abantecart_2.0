<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcProductDescription
 * 
 * @property int $product_id
 * @property int $language_id
 * @property string $name
 * @property string $meta_keywords
 * @property string $meta_description
 * @property string $description
 * @property string $blurb
 * 
 * @property \App\Models\AcProduct $ac_product
 * @property \App\Models\AcLanguage $ac_language
 *
 * @package App\Models
 */
class AcProductDescription extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'product_id' => 'int',
		'language_id' => 'int'
	];

	protected $fillable = [
		'name',
		'meta_keywords',
		'meta_description',
		'description',
		'blurb'
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
