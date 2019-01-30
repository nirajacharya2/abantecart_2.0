<?php

namespace abc\models\system;

use abc\models\BaseModel;

/**
 * Class TaxRateDescription
 *
 * @property int $tax_rate_id
 * @property int $language_id
 * @property string $description
 *
 * @property TaxRate $tax_rate
 * @property Language $language
 *
 * @package abc\models
 */
class TaxRateDescription extends BaseModel
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
