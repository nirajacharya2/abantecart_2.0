<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class ResourceDescription
 *
 * @property int $resource_id
 * @property int $language_id
 * @property string $name
 * @property string $title
 * @property string $description
 * @property string $resource_path
 * @property string $resource_code
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @property \abc\models\base\ResourceLibrary $resource_library
 * @property \abc\models\base\Language $language
 *
 * @package abc\models
 */
class ResourceDescription extends AModelBase
{
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'resource_id' => 'int',
        'language_id' => 'int',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'name',
        'title',
        'description',
        'resource_path',
        'resource_code',
        'date_added',
        'date_modified',
    ];

    public function resource_library()
    {
        return $this->belongsTo(ResourceLibrary::class, 'resource_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
