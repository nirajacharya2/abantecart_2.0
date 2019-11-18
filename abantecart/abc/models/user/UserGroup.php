<?php

namespace abc\models\user;

use abc\models\BaseModel;
use abc\core\lib\AException;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class UserGroup
 *
 * @property int $user_group_id
 * @property string $name
 * @property string $permission
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @property \Illuminate\Database\Eloquent\Collection $users
 *
 * @package abc\models
 */
class UserGroup extends BaseModel
{
    use SoftDeletes;

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
     *
     * @throws AException
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes = []);
        if (!$this->isUser()) {
            throw new AException ('Error: permission denied to access '.__CLASS__, AC_ERR_LOAD);
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
