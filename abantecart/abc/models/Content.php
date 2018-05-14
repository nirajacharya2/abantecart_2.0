<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcContent
 * 
 * @property int $content_id
 * @property int $parent_content_id
 * @property int $sort_order
 * @property int $status
 * 
 * @property \Illuminate\Database\Eloquent\Collection $ac_content_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $ac_contents_to_stores
 *
 * @package App\Models
 */
class AcContent extends Eloquent
{
	public $timestamps = false;

	protected $casts = [
		'parent_content_id' => 'int',
		'sort_order' => 'int',
		'status' => 'int'
	];

	protected $fillable = [
		'sort_order',
		'status'
	];

	public function ac_content_descriptions()
	{
		return $this->hasMany(\App\Models\AcContentDescription::class, 'content_id');
	}

	public function ac_contents_to_stores()
	{
		return $this->hasMany(\App\Models\AcContentsToStore::class, 'content_id');
	}
}
