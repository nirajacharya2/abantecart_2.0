<?php

namespace abc\models\base;

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
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'extension_id'        => 'int',
        'extension_parent_id' => 'int',
    ];
}
