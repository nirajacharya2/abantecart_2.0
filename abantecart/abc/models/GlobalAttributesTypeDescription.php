<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class GlobalAttributesTypeDescription
 *
 * @property int            $attribute_type_id
 * @property int            $language_id
 * @property string         $type_name
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @package abc\models
 */
class GlobalAttributesTypeDescription extends AModelBase
{
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'attribute_type_id' => 'int',
        'language_id'       => 'int',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'type_name',
        'date_added',
        'date_modified',
    ];
}
