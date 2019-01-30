<?php

namespace abc\models\locale;

use abc\models\BaseModel;

/**
 * Class ZoneDescription
 *
 * @property int $zone_id
 * @property int $language_id
 * @property string $name
 *
 * @property Zone $zone
 * @property Language $language
 *
 * @package abc\models
 */
class ZoneDescription extends BaseModel
{
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'zone_id'     => 'int',
        'language_id' => 'int',
    ];

    protected $fillable = [
        'name',
    ];

    public function zone()
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
