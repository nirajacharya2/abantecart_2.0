<?php

namespace abc\models\catalog;

use abc\models\BaseModel;

/**
 * Class DownloadAttributeValue
 *
 * @property int $download_attribute_id
 * @property int $attribute_id
 * @property int $download_id
 * @property string $attribute_value_ids
 *
 * @property Download $download
 *
 * @package abc\models
 */
class DownloadAttributeValue extends BaseModel
{
    protected $primaryKey = 'download_attribute_id';
    public $timestamps = false;

    protected $casts = [
        'attribute_id' => 'int',
        'download_id'  => 'int',
    ];

    protected $fillable = [
        'attribute_id',
        'download_id',
        'attribute_value_ids',
    ];

    public function download()
    {
        return $this->belongsTo(Download::class, 'download_id');
    }
}
