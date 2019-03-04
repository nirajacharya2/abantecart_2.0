<?php

namespace abc\models\system;

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
 * @property string $main_auditable_model
 * @property int $main_auditable_id
 * @property string $auditable_model
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
        'main_auditable_model',
        'main_auditable_id',
        'auditable_model',
        'auditable_id',
        'old_value',
        'new_value',
    ];

    public static $auditingEnabled = false;
    public static $auditEvents = [];

    public function user()
    {
        return $this->morphTo();
    }


    public function auditable() {
        return $this->morphTo();
    }


}
