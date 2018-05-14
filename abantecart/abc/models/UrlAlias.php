<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class AcUrlAlias
 *
 * @property int                    $url_alias_id
 * @property string                 $query
 * @property string                 $keyword
 * @property int                    $language_id
 *
 * @property \abc\models\AcLanguage $language
 *
 * @package abc\models
 */
class UrlAlias extends AModelBase
{
    protected $primaryKey = 'url_alias_id';
    public $timestamps = false;

    protected $casts = [
        'language_id' => 'int',
    ];

    protected $fillable = [
        'query',
        'keyword',
        'language_id',
    ];

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
