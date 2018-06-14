<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class GlobalAttributesType
 *
 * @property int $attribute_type_id
 * @property string $type_key
 * @property string $controller
 * @property int $sort_order
 * @property int $status
 *
 * @package abc\models
 */
class GlobalAttributesType extends AModelBase
{
    protected $primaryKey = 'attribute_type_id';
    public $timestamps = false;

    protected $casts = [
        'sort_order' => 'int',
        'status'     => 'int',
    ];

    protected $fillable = [
        'type_key',
        'controller',
        'sort_order',
        'status',
    ];
}
