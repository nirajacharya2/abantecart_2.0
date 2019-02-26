<?php

namespace abc\models\system;

use abc\models\BaseModel;

/**
 * Class ExtensionDependency
 *
 * @property int $extension_id
 * @property int $extension_parent_id
 *
 * @package abc\models
 */
class ExtensionDependency extends BaseModel
{
    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'extension_id',
        'extension_parent_id'
    ];

    public $timestamps = false;

    protected $casts = [
        'extension_id'        => 'int',
        'extension_parent_id' => 'int',
    ];
}
