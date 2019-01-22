<?php

namespace abc\models\base;

use abc\models\BaseModel;

/**
 * Class CustomerGroup
 *
 * @property int $customer_group_id
 * @property string $name
 * @property bool $tax_exempt
 *
 * @package abc\models
 */
class CustomerGroup extends BaseModel
{
    protected $primaryKey = 'customer_group_id';
    public $timestamps = false;

    protected $casts = [
        'tax_exempt' => 'bool',
    ];

    protected $fillable = [
        'name',
        'tax_exempt',
    ];
}
