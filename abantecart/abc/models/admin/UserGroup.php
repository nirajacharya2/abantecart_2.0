<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcUserGroup
 * 
 * @property int $user_group_id
 * @property string $name
 * @property string $permission
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 * 
 * @property \Illuminate\Database\Eloquent\Collection $ac_users
 *
 * @package App\Models
 */
class AcUserGroup extends Eloquent
{
	protected $primaryKey = 'user_group_id';
	public $timestamps = false;

	protected $dates = [
		'date_added',
		'date_modified'
	];

	protected $fillable = [
		'name',
		'permission',
		'date_added',
		'date_modified'
	];

	public function ac_users()
	{
		return $this->hasMany(\App\Models\AcUser::class, 'user_group_id');
	}
}
