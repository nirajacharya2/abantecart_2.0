<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class AcDownloadAttributeValue
 *
 * @property int                    $download_attribute_id
 * @property int                    $attribute_id
 * @property int                    $download_id
 * @property string                 $attribute_value_ids
 *
 * @property \abc\models\AcDownload $download
 *
 * @package abc\models
 */
class DownloadAttributeValue extends AModelBase
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
