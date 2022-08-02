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
use abc\core\lib\AException;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class UserGroup
 *
 * @property int $user_group_id
 * @property string $name
 * @property string $permission
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @property Collection $users
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
     * @return HasMany
     */
    public function users()
    {
        return $this->hasMany(User::class, 'user_group_id');
    }
}
