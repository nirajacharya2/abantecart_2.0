<?php

namespace abc\models\locale;

use abc\models\BaseModel;

/**
 * Class LengthClassDescription
 *
 * @property int $length_class_id
 * @property int $language_id
 * @property string $title
 * @property string $unit
 *
 * @property Language $language
 *
 * @package abc\models
 */
class LengthClassDescription extends BaseModel
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
