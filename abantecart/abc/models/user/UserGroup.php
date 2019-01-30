<?php

namespace abc\models\user;

use abc\models\BaseModel;
use abc\core\lib\AException;

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

    /**
     * UserGroup constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes = []);
        if (!$this->isUser()) {
            throw new AException (AC_ERR_LOAD, 'Error: permission denied to access ' . __CLASS__);
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users()
    {
        return $this->hasMany(User::class, 'user_group_id');
    }
}
