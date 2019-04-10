<?php

namespace abc\models\system;

use abc\models\BaseModel;

class AuditUser extends BaseModel
{
    const USER_TYPES = [
        'root' => 1,
        'system' => 2,
        'storefront' => 3,
    ];
    public $timestamps = false;

    protected $fillable = [
        'user_type',
        'user_id',
        'name',
    ];

    public static $auditingEnabled = false;
    public static $auditEvents = [];

}
