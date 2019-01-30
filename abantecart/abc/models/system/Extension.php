<?php

namespace abc\models\system;

use abc\models\BaseModel;

/**
 * Class Extension
 *
 * @property int $extension_id
 * @property string $type
 * @property string $key
 * @property string $category
 * @property int $status
 * @property int $priority
 * @property string $version
 * @property string $license_key
 * @property \Carbon\Carbon $date_installed
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @package abc\models
 */
class Extension extends BaseModel
{
    protected $primaryKey = 'extension_id';
    public $timestamps = false;

    protected $casts = [
        'status'   => 'int',
        'priority' => 'int',
    ];

    protected $dates = [
        'date_installed',
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'type',
        'key',
        'category',
        'status',
        'priority',
        'version',
        'license_key',
        'date_installed',
        'date_added',
        'date_modified',
    ];
}
