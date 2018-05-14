<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class AcZoneDescription
 *
 * @property int                    $zone_id
 * @property int                    $language_id
 * @property string                 $name
 *
 * @property \abc\models\AcZone     $zone
 * @property \abc\models\AcLanguage $language
 *
 * @package abc\models
 */
class ZoneDescription extends AModelBase
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
