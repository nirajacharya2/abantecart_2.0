<?php

namespace abc\models;

/**
 * Class AcUserGroup
 *
 * @property int                                      $user_group_id
 * @property string                                   $name
 * @property string                                   $permission
 * @property \Carbon\Carbon                           $date_added
 * @property \Carbon\Carbon                           $date_modified
 *
 * @property \Illuminate\Database\Eloquent\Collection $users
 *
 * @package abc\models
 */
class UserGroup extends BaseModel
{
    protected $primaryKey = 'user_group_id';
    public $timestamps = false;

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'name',
        'permission',
        'date_added',
        'date_modified',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'user_group_id');
    }
}
