<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class AcGlobalAttributesGroupsDescription
 *
 * @property int $attribute_group_id
 * @property int $language_id
 * @property string $name
 *
 * @package abc\models
 */
class GlobalAttributesGroupsDescription extends AModelBase
{
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'attribute_group_id' => 'int',
        'language_id'        => 'int',
    ];

    protected $fillable = [
        'name',
    ];
}
