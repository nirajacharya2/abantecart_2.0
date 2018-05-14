<?php

namespace abc\models;

/**
 * Class AcUser
 *
 * @property int                                      $user_id
 * @property int                                      $user_group_id
 * @property string                                   $username
 * @property string                                   $salt
 * @property string                                   $password
 * @property string                                   $firstname
 * @property string                                   $lastname
 * @property string                                   $email
 * @property int                                      $status
 * @property string                                   $ip
 * @property \Carbon\Carbon                           $last_login
 * @property \Carbon\Carbon                           $date_added
 * @property \Carbon\Carbon                           $date_modified
 *
 * @property UserGroup                                $user_group
 * @property \Illuminate\Database\Eloquent\Collection $user_notifications
 *
 * @package abc\models
 */
class User extends AModelBase
{
    protected $primaryKey = 'user_id';
    public $timestamps = false;

    protected $casts = [
        'user_group_id' => 'int',
        'status'        => 'int',
    ];

    protected $dates = [
        'last_login',
        'date_added',
        'date_modified',
    ];

    protected $hidden = [
        'password',
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
        'date_modified',
    ];

    public function user_group()
    {
        return $this->belongsTo(UserGroup::class, 'user_group_id');
    }

    public function user_notifications()
    {
        return $this->hasMany(UserNotification::class, 'user_id');
    }
}
