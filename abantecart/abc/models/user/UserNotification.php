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
use abc\models\system\Store;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class UserNotification
 *
 * @property int $user_id
 * @property int $store_id
 * @property bool $section
 * @property string $sendpoint
 * @property string $protocol
 * @property string $uri
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @property User $user
 * @property Store $store
 *
 * @package abc\models
 */
class UserNotification extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'user_id',
        'store_id',
    ];

    public $timestamps = false;

    protected $casts = [
        'user_id'       => 'int',
        'store_id'      => 'int',
        'section'       => 'bool',
        'date_added'    => 'datetime',
        'date_modified' => 'datetime'
    ];

    protected $fillable = [
        'uri',
        'date_added',
        'date_modified',
    ];

    /**
     * UserNotification constructor.
     *
     * @param array $attributes
     *
     * @throws AException
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        if (!$this->isUser()) {
            throw new AException ('Error: permission denied to access '.__CLASS__, AC_ERR_LOAD);
        }
    }

    /**
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo
     */
    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
}
