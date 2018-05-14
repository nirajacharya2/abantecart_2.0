<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class AcExtensionDependency
 *
 * @property int $extension_id
 * @property int $extension_parent_id
 *
 * @package abc\models
 */
class ExtensionDependency extends AModelBase
{
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'extension_id'        => 'int',
        'extension_parent_id' => 'int',
    ];
}
