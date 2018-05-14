<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class ContentDescription
 *
 * @property int                  $content_id
 * @property int                  $language_id
 * @property string               $name
 * @property string               $title
 * @property string               $description
 * @property \Carbon\Carbon       $date_added
 * @property \Carbon\Carbon       $date_modified
 *
 * @property \abc\models\Content  $content
 * @property \abc\models\Language $language
 *
 * @package abc\models
 */
class ContentDescription extends AModelBase
{
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'content_id'  => 'int',
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
        'content',
        'date_added',
        'date_modified',
    ];

    public function content()
    {
        return $this->belongsTo(Content::class, 'content_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
