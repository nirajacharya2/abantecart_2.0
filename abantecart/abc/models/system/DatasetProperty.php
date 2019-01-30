<?php

namespace abc\models\system;

use abc\models\BaseModel;

/**
 * Class DatasetProperty
 *
 * @property int $rowid
 * @property int $dataset_id
 * @property string $dataset_property_name
 * @property string $dataset_property_value
 *
 * @property Dataset $dataset
 *
 * @package abc\models
 */
class DatasetProperty extends BaseModel
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
