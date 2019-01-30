<?php

namespace abc\models\catalog;

use abc\models\BaseModel;

/**
 * Class GlobalAttributesGroupsDescription
 *
 * @property int $attribute_group_id
 * @property int $language_id
 * @property string $name
 *
 * @package abc\models
 */
class GlobalAttributesGroupsDescription extends BaseModel
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
