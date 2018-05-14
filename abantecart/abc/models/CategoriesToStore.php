<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcCategoriesToStore
 * 
 * @property int $category_id
 * @property int $store_id
 * 
 * @property \App\Models\AcCategory $ac_category
 * @property \App\Models\AcStore $ac_store
 *
 * @package App\Models
 */
class AcCategoriesToStore extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'category_id' => 'int',
		'store_id' => 'int'
	];

	public function ac_category()
	{
		return $this->belongsTo(\App\Models\AcCategory::class, 'category_id');
	}

	public function ac_store()
	{
		return $this->belongsTo(\App\Models\AcStore::class, 'store_id');
	}
}
