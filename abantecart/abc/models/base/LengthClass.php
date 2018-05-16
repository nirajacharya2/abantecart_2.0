<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class AcLengthClass
 *
 * @property int $length_class_id
 * @property float $value
 * @property string $iso_code
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @package abc\models
 */
class LengthClass extends AModelBase
{
    public $timestamps = false;

    protected $casts = [
        'value' => 'float',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'value',
        'date_added',
        'date_modified',
    ];
}
