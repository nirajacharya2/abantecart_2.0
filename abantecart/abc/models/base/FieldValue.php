<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class FieldValue
 *
 * @property int $value_id
 * @property int $field_id
 * @property string $value
 * @property int $language_id
 *
 * @property Field $field
 *
 * @package abc\models
 */
class FieldValue extends AModelBase
{
    protected $primaryKey = 'value_id';
    public $timestamps = false;

    protected $casts = [
        'field_id'    => 'int',
        'language_id' => 'int',
    ];

    protected $fillable = [
        'field_id',
        'value',
        'language_id',
    ];

    public function field()
    {
        return $this->belongsTo(Field::class, 'field_id');
    }
}
