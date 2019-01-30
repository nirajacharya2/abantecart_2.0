<?php

namespace abc\models\catalog;

use abc\models\BaseModel;

/**
 * Class ProductFilterDescription
 *
 * @property int $filter_id
 * @property string $value
 * @property int $language_id
 *
 * @package abc\models
 */
class ProductFilterDescription extends BaseModel
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
