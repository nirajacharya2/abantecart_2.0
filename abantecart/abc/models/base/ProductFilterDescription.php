<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class AcProductFilterDescription
 *
 * @property int $filter_id
 * @property string $value
 * @property int $language_id
 *
 * @package abc\models
 */
class ProductFilterDescription extends AModelBase
{
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'filter_id'   => 'int',
        'language_id' => 'int',
    ];

    protected $fillable = [
        'value',
    ];
}
