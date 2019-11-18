<?php

namespace abc\models\user;

use abc\models\BaseModel;

use abc\core\lib\AException;
use abc\models\system\Store;
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
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
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
        'user_id'  => 'int',
        'store_id' => 'int',
        'section'  => 'bool',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
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
        parent::__construct($attributes = []);
        if (!$this->isUser()) {
            throw new AException ('Error: permission denied to access '.__CLASS__, AC_ERR_LOAD);
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
}
