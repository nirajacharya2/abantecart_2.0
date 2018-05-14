<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class AcTaxClassDescription
 *
 * @property int                    $tax_class_id
 * @property int                    $language_id
 * @property string                 $title
 * @property string                 $description
 *
 * @property \abc\models\AcTaxClass $tax_class
 * @property \abc\models\AcLanguage $language
 *
 * @package abc\models
 */
class TaxClassDescription extends AModelBase
{
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'tax_class_id' => 'int',
        'language_id'  => 'int',
    ];

    protected $fillable = [
        'title',
        'description',
    ];

    public function tax_class()
    {
        return $this->belongsTo(TaxClass::class, 'tax_class_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
