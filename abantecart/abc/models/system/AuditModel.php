<?php

namespace abc\models\system;

use abc\models\BaseModel;

class AuditModel extends BaseModel
{
    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    public static $auditingEnabled = false;
    public static $auditEvents = [];


}
