<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2022 Belavier Commerce LLC
 *
 * This source file is subject to Open Software License (OSL 3.0)
 * License details is bundled with this package in the file LICENSE.txt.
 * It is also available at this URL:
 * <http://www.opensource.org/licenses/OSL-3.0>
 *
 * UPGRADE NOTE:
 * Do not edit or add to this file if you wish to upgrade AbanteCart to newer
 * versions in the future. If you wish to customize AbanteCart for your
 * needs please refer to http://www.abantecart.com for more information.
 */
namespace abc\models\user;

use abc\models\BaseModel;
use abc\models\system\Audit;
use Carbon\Carbon;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
 * @property Carbon $last_login
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @property UserGroup $user_group
 * @property Collection $user_notifications
 *
 *
 * @method static User find(int $user_id) User
 * @method static User select(mixed $select) Builder
 *
 * @package abc\models
 */
class User extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['notifications'];

    protected $primaryKey = 'user_id';
    public $timestamps = false;

    protected $casts = [
        'user_group_id' => 'int',
        'status'        => 'int',
        'ip'            => 'string'
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

    /**
     * @return BelongsTo
     */
    public function user_group()
    {
        return $this->belongsTo(UserGroup::class, 'user_group_id');
    }

    /**
     * @return HasMany
     */
    public function notifications()
    {
        return $this->hasMany(UserNotification::class, 'user_id');
    }

    /**
     * @return MorphMany
     */
    public function audits()
    {
        return $this->morphMany(Audit::class, 'user');
    }
}
