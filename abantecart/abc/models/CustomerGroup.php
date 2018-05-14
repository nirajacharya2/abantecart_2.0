<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class CustomerGroup
 *
 * @property int    $customer_group_id
 * @property string $name
 * @property bool   $tax_exempt
 *
 * @package abc\models
 */
class CustomerGroup extends AModelBase
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
