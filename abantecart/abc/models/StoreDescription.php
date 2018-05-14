<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcStoreDescription
 * 
 * @property int $store_id
 * @property int $language_id
 * @property string $description
 * @property string $title
 * @property string $meta_description
 * @property string $meta_keywords
 * 
 * @property \App\Models\AcStore $ac_store
 * @property \App\Models\AcLanguage $ac_language
 *
 * @package App\Models
 */
class AcStoreDescription extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'store_id' => 'int',
		'language_id' => 'int'
	];

	protected $fillable = [
		'description',
		'title',
		'meta_description',
		'meta_keywords'
	];

	public function ac_store()
	{
		return $this->belongsTo(\App\Models\AcStore::class, 'store_id');
	}

	public function ac_language()
	{
		return $this->belongsTo(\App\Models\AcLanguage::class, 'language_id');
	}
}
