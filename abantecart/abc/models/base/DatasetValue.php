<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class DatasetValue
 *
 * @property int $dataset_column_id
 * @property int $value_integer
 * @property float $value_float
 * @property string $value_varchar
 * @property string $value_text
 * @property \Carbon\Carbon $value_timestamp
 * @property bool $value_boolean
 * @property int $value_sort_order
 * @property int $row_id
 *
 * @property \abc\models\DatasetDefinition $dataset_definition
 *
 * @package abc\models
 */
class DatasetValue extends AModelBase
{
    protected $primaryKey = 'value_sort_order';
    public $timestamps = false;

    protected $casts = [
        'dataset_column_id' => 'int',
        'value_integer'     => 'int',
        'value_float'       => 'float',
        'value_boolean'     => 'bool',
        'row_id'            => 'int',
    ];

    protected $dates = [
        'value_timestamp',
    ];

    protected $fillable = [
        'dataset_column_id',
        'value_integer',
        'value_float',
        'value_varchar',
        'value_text',
        'value_timestamp',
        'value_boolean',
        'row_id',
    ];

    public function dataset_definition()
    {
        return $this->belongsTo(DatasetDefinition::class, 'dataset_column_id');
    }
}
