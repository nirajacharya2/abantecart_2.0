<?php

namespace abc\models\base;

use abc\models\BaseModel;

/**
 * Class GlobalAttributesGroup
 *
 * @property int $attribute_group_id
 * @property int $sort_order
 * @property int $status
 *
 * @package abc\models
 */
class GlobalAttributesGroup extends BaseModel
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
