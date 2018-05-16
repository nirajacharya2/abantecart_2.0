<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class AcProductFilterRangesDescription
 *
 * @property int $range_id
 * @property string $name
 * @property int $language_id
 *
 * @package abc\models
 */
class ProductFilterRangesDescription extends AModelBase
{
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'range_id'    => 'int',
        'language_id' => 'int',
    ];

    protected $fillable = [
        'name',
    ];
}
