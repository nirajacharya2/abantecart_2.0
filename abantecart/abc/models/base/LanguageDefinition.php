<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class AcLanguageDefinition
 *
 * @property int $language_definition_id
 * @property int $language_id
 * @property bool $section
 * @property string $block
 * @property string $language_key
 * @property string $language_value
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @property \abc\models\AcLanguage $language
 *
 * @package abc\models
 */
class LanguageDefinition extends AModelBase
{
    public $timestamps = false;

    protected $casts = [
        'language_id' => 'int',
        'section'     => 'bool',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'language_value',
        'date_added',
        'date_modified',
    ];

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
