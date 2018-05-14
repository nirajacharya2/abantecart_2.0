<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class AcGlobalAttributesGroup
 *
 * @property int $attribute_group_id
 * @property int $sort_order
 * @property int $status
 *
 * @package abc\models
 */
class GlobalAttributesGroup extends AModelBase
{
    protected $primaryKey = 'attribute_group_id';
    public $timestamps = false;

    protected $casts = [
        'sort_order' => 'int',
        'status'     => 'int',
    ];

    protected $fillable = [
        'sort_order',
        'status',
    ];
}
