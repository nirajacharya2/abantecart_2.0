<?php

/**
 * Created by Reliese Model.
 * Date: Sun, 13 May 2018 01:25:45 +0000.
 */

namespace App\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class AcUser
 * 
 * @property int $user_id
 * @property int $user_group_id
 * @property string $username
 * @property string $salt
 * @property string $password
 * @property string $firstname
 * @property string $lastname
 * @property string $email
 * @property int $status
 * @property string $ip
 * @property \Carbon\Carbon $last_login
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 * 
 * @property \App\Models\AcUserGroup $ac_user_group
 * @property \Illuminate\Database\Eloquent\Collection $ac_user_notifications
 *
 * @package App\Models
 */
class AcUser extends Eloquent
{
	protected $primaryKey = 'user_id';
	public $timestamps = false;

	protected $casts = [
		'user_group_id' => 'int',
		'status' => 'int'
	];

	protected $dates = [
		'last_login',
		'date_added',
		'date_modified'
	];

	protected $hidden = [
		'password'
	];

	protected $fillable = [
		'user_group_id',
		'username',
		'salt',
		'password',
		'firstname',
		'lastname',
		'email',
		'status',
		'ip',
		'last_login',
		'date_added',
		'date_modified'
	];

	public function ac_user_group()
	{
		return $this->belongsTo(\App\Models\AcUserGroup::class, 'user_group_id');
	}

	public function ac_user_notifications()
	{
		return $this->hasMany(\App\Models\AcUserNotification::class, 'user_id');
	}
}
