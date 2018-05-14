<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcUserNotification
 * 
 * @property int $user_id
 * @property int $store_id
 * @property bool $section
 * @property string $sendpoint
 * @property string $protocol
 * @property string $uri
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 * 
 * @property \App\Models\AcUser $ac_user
 * @property \App\Models\AcStore $ac_store
 *
 * @package App\Models
 */
class AcUserNotification extends Eloquent
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'user_id' => 'int',
		'store_id' => 'int',
		'section' => 'bool'
	];

	protected $dates = [
		'date_added',
		'date_modified'
	];

	protected $fillable = [
		'uri',
		'date_added',
		'date_modified'
	];

	public function ac_user()
	{
		return $this->belongsTo(\App\Models\AcUser::class, 'user_id');
	}

	public function ac_store()
	{
		return $this->belongsTo(\App\Models\AcStore::class, 'store_id');
	}
}
