<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcContentsToStore
 * 
 * @property int $content_id
 * @property int $store_id
 * 
 * @property \App\Models\AcContent $ac_content
 * @property \App\Models\AcStore $ac_store
 *
 * @package App\Models
 */
class AcContentsToStore extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'content_id' => 'int',
		'store_id' => 'int'
	];

	public function ac_content()
	{
		return $this->belongsTo(\App\Models\AcContent::class, 'content_id');
	}

	public function ac_store()
	{
		return $this->belongsTo(\App\Models\AcStore::class, 'store_id');
	}
}
