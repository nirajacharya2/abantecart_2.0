<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class AcDatasetColumnProperty
 *
 * @property int                             $rowid
 * @property int                             $dataset_column_id
 * @property string                          $dataset_column_property_name
 * @property string                          $dataset_column_property_value
 *
 * @property \abc\models\AcDatasetDefinition $dataset_definition
 *
 * @package abc\models
 */
class DatasetColumnProperty extends AModelBase
{
    protected $primaryKey = 'rowid';
    public $timestamps = false;

    protected $casts = [
        'dataset_column_id' => 'int',
    ];

    protected $fillable = [
        'dataset_column_id',
        'dataset_column_property_name',
        'dataset_column_property_value',
    ];

    public function dataset_definition()
    {
        return $this->belongsTo(DatasetDefinition::class, 'dataset_column_id');
    }
}
