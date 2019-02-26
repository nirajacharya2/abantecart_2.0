<?php

namespace abc\models\catalog;

use abc\models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    use SoftDeletes;

    protected $primaryKeySet = [
        'filter_id',
        'language_id',
    ];

    public $timestamps = false;

    protected $casts = [
        'filter_id'   => 'int',
        'language_id' => 'int',
    ];

    protected $fillable = [
        'value',
    ];
}
