<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class TaxRateDescription
 *
 * @property int $tax_rate_id
 * @property int $language_id
 * @property string $description
 *
 * @property \abc\models\base\TaxRate $tax_rate
 * @property \abc\models\base\Language $language
 *
 * @package abc\models
 */
class TaxRateDescription extends AModelBase
{
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'tax_rate_id' => 'int',
        'language_id' => 'int',
    ];

    protected $fillable = [
        'description',
    ];

    public function tax_rate()
    {
        return $this->belongsTo(TaxRate::class, 'tax_rate_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
