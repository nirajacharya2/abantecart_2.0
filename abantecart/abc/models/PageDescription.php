<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class PageDescription
 *
 * @property int                  $page_id
 * @property int                  $language_id
 * @property string               $name
 * @property string               $title
 * @property string               $seo_url
 * @property string               $keywords
 * @property string               $description
 * @property string               $content
 * @property \Carbon\Carbon       $date_added
 * @property \Carbon\Carbon       $date_modified
 *
 * @property \abc\models\Page     $page
 * @property \abc\models\Language $language
 *
 * @package abc\models
 */
class PageDescription extends AModelBase
{
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'page_id'     => 'int',
        'language_id' => 'int',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'name',
        'title',
        'seo_url',
        'keywords',
        'description',
        'content',
        'date_added',
        'date_modified',
    ];

    public function page()
    {
        return $this->belongsTo(Page::class, 'page_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
