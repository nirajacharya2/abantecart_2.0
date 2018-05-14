<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class AcProductFilterRange
 *
 * @property int   $range_id
 * @property int   $feature_id
 * @property int   $filter_id
 * @property float $from
 * @property float $to
 * @property int   $sort_order
 *
 * @package abc\models
 */
class ProductFilterRange extends AModelBase
{
    protected $primaryKey = 'range_id';
    public $timestamps = false;

    protected $casts = [
        'feature_id' => 'int',
        'filter_id'  => 'int',
        'from'       => 'float',
        'to'         => 'float',
        'sort_order' => 'int',
    ];

    protected $fillable = [
        'feature_id',
        'filter_id',
        'from',
        'to',
        'sort_order',
    ];
}
