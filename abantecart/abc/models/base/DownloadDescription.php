<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class DownloadDescription
 *
 * @property int $download_id
 * @property int $language_id
 * @property string $name
 *
 * @property Download $download
 * @property Language $language
 *
 * @package abc\models
 */
class DownloadDescription extends AModelBase
{
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'download_id' => 'int',
        'language_id' => 'int',
    ];

    protected $fillable = [
        'name',
    ];

    public function download()
    {
        return $this->belongsTo(Download::class, 'download_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
