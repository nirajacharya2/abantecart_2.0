<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class AcFieldDescription
 *
 * @property int $field_id
 * @property string $name
 * @property string $description
 * @property int $language_id
 * @property string $error_text
 *
 * @property \abc\models\AcField $field
 * @property \abc\models\AcLanguage $language
 *
 * @package abc\models
 */
class FieldDescription extends AModelBase
{
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'field_id'    => 'int',
        'language_id' => 'int',
    ];

    protected $fillable = [
        'name',
        'description',
        'error_text',
    ];

    public function field()
    {
        return $this->belongsTo(Field::class, 'field_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
