<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class WeightClassDescription
 *
 * @property int                     $weight_class_id
 * @property int                     $language_id
 * @property string                  $title
 * @property string                  $unit
 *
 * @property \abc\models\WeightClass $weight_class
 * @property \abc\models\Language    $language
 *
 * @package abc\models
 */
class WeightClassDescription extends AModelBase
{
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'weight_class_id' => 'int',
        'language_id'     => 'int',
    ];

    protected $fillable = [
        'title',
        'unit',
    ];

    public function weight_class()
    {
        return $this->belongsTo(WeightClass::class, 'weight_class_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
