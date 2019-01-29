<?php

namespace abc\models\base;

use abc\models\admin\User;
use abc\models\base\Customer;
use abc\models\BaseModel;

/**
 * Class Audit
 *
 * @property string $user_type
 * @property int $user_id
 * @property string $user_name
 * @property int $alias_id
 * @property string $alias_name
 * @property string $event
 * @property string $request_id
 * @property string $session_id
 * @property string $auditable_type
 * @property string $auditable_name
 * @property int $auditable_id
 * @property string $old_value
 * @property string $new_value
 *
 *
 * @package abc\models
 */
class Audit extends BaseModel
{
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'user_type',
        'user_id',
        'user_name',
        'alias_id',
        'alias_name',
        'event',
        'request_id',
        'session_id',
        'auditable_type',
        'auditable_name',
        'auditable_id',
        'old_value',
        'new_value',
    ];

    public function user()
    {
        return $this->morphTo();
    }


    public function auditable() {
        return $this->morphTo();
    }


}
