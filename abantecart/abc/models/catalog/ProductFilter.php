<?php

namespace abc\models\catalog;

use abc\models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ProductFilter
 *
 * @property int $filter_id
 * @property string $filter_type
 * @property string $categories_hash
 * @property int $feature_id
 * @property int $sort_order
 * @property int $status
 *
 * @package abc\models
 */
class ProductFilter extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'filter_id';
    public $timestamps = false;

    protected $casts = [
        'feature_id' => 'int',
        'sort_order' => 'int',
        'status'     => 'int',
    ];

    protected $fillable = [
        'filter_type',
        'categories_hash',
        'feature_id',
        'sort_order',
        'status',
    ];
}
