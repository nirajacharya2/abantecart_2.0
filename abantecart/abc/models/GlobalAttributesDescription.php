<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class GlobalAttributesDescription
 *
 * @property int                         $attribute_id
 * @property int                         $language_id
 * @property string                      $name
 * @property string                      $placeholder
 * @property string                      $error_text
 *
 * @property \abc\models\GlobalAttribute $global_attribute
 * @property \abc\models\Language        $language
 *
 * @package abc\models
 */
class GlobalAttributesDescription extends AModelBase
{
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'attribute_id' => 'int',
        'language_id'  => 'int',
    ];

    protected $fillable = [
        'name',
        'placeholder',
        'error_text',
    ];

    public function global_attribute()
    {
        return $this->belongsTo(GlobalAttribute::class, 'attribute_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
