<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcCategoryDescription
 * 
 * @property int $category_id
 * @property int $language_id
 * @property string $name
 * @property string $meta_keywords
 * @property string $meta_description
 * @property string $description
 * 
 * @property \App\Models\AcCategory $ac_category
 * @property \App\Models\AcLanguage $ac_language
 *
 * @package App\Models
 */
class AcCategoryDescription extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'category_id' => 'int',
		'language_id' => 'int'
	];

	protected $fillable = [
		'name',
		'meta_keywords',
		'meta_description',
		'description'
	];

	public function ac_category()
	{
		return $this->belongsTo(\App\Models\AcCategory::class, 'category_id');
	}

	public function ac_language()
	{
		return $this->belongsTo(\App\Models\AcLanguage::class, 'language_id');
	}
}
