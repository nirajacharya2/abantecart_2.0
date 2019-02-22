<?php

namespace abc\models\catalog;

use abc\models\BaseModel;
use abc\models\locale\Language;
use Illuminate\Database\Eloquent\SoftDeletes;

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
class DownloadDescription extends BaseModel
{
    use SoftDeletes;
    const DELETED_AT = 'date_deleted';

    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'download_id',
        'language_id'
    ];

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
