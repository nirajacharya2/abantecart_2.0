<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class AcLengthClassDescription
 *
 * @property int                    $length_class_id
 * @property int                    $language_id
 * @property string                 $title
 * @property string                 $unit
 *
 * @property \abc\models\AcLanguage $language
 *
 * @package abc\models
 */
class LengthClassDescription extends AModelBase
{
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'length_class_id' => 'int',
        'language_id'     => 'int',
    ];

    protected $fillable = [
        'title',
        'unit',
    ];

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
