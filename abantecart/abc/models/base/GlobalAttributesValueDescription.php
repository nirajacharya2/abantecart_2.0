<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class GlobalAttributesValueDescription
 *
 * @property int $attribute_value_id
 * @property int $attribute_id
 * @property int $language_id
 * @property string $value
 *
 * @property GlobalAttribute $global_attribute
 * @property Language $language
 *
 * @package abc\models
 */
class GlobalAttributesValueDescription extends AModelBase
{
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'attribute_value_id' => 'int',
        'attribute_id'       => 'int',
        'language_id'        => 'int',
    ];

    protected $fillable = [
        'value',
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
