<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class AcCountryDescription
 *
 * @property int                    $country_id
 * @property int                    $language_id
 * @property string                 $name
 *
 * @property \abc\models\AcCountry  $country
 * @property \abc\models\AcLanguage $language
 *
 * @package abc\models
 */
class CountryDescription extends AModelBase
{
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'country_id'  => 'int',
        'language_id' => 'int',
    ];

    protected $fillable = [
        'name',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
