<?php

namespace abc\models\catalog;

use abc\models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    use SoftDeletes;

    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'range_id',
        'language_id',
    ];
    public $timestamps = false;

    protected $casts = [
        'range_id'    => 'int',
        'language_id' => 'int',
    ];

    protected $fillable = [
        'name',
    ];
}
