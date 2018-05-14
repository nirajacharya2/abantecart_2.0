<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcCategory
 * 
 * @property int $category_id
 * @property int $parent_id
 * @property int $sort_order
 * @property int $status
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 * 
 * @property \Illuminate\Database\Eloquent\Collection $ac_categories_to_stores
 * @property \Illuminate\Database\Eloquent\Collection $ac_category_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_products_to_categories
 *
 * @package App\Models
 */
class AcCategory extends Eloquent
{
	protected $primaryKey = 'category_id';
	public $timestamps = false;

	protected $casts = [
		'parent_id' => 'int',
		'sort_order' => 'int',
		'status' => 'int'
	];

	protected $dates = [
		'date_added',
		'date_modified'
	];

	protected $fillable = [
		'parent_id',
		'sort_order',
		'status',
		'date_added',
		'date_modified'
	];

	public function ac_categories_to_stores()
	{
		return $this->hasMany(\App\Models\AcCategoriesToStore::class, 'category_id');
	}

	public function ac_category_descriptions()
	{
		return $this->hasMany(\App\Models\AcCategoryDescription::class, 'category_id');
	}

	public function ac_products_to_categories()
	{
		return $this->hasMany(\App\Models\AcProductsToCategory::class, 'category_id');
	}
}
