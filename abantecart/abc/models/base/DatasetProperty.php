<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class AcDatasetProperty
 *
 * @property int $rowid
 * @property int $dataset_id
 * @property string $dataset_property_name
 * @property string $dataset_property_value
 *
 * @property \abc\models\AcDataset $dataset
 *
 * @package abc\models
 */
class DatasetProperty extends AModelBase
{
    protected $primaryKey = 'rowid';
    public $timestamps = false;

    protected $casts = [
        'dataset_id' => 'int',
    ];

    protected $fillable = [
        'dataset_id',
        'dataset_property_name',
        'dataset_property_value',
    ];

    public function dataset()
    {
        return $this->belongsTo(Dataset::class, 'dataset_id');
    }
}
