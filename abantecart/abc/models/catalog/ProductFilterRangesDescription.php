<?php

namespace abc\models\catalog;

use abc\models\BaseModel;

/**
 * Class ProductFilterRangesDescription
 *
 * @property int $range_id
 * @property string $name
 * @property int $language_id
 *
 * @package abc\models
 */
class ProductFilterRangesDescription extends BaseModel
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
