<?php

namespace abc\models\system;

use abc\models\BaseModel;
use abc\models\locale\Language;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    use SoftDeletes;

    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'tax_rate_id',
        'language_id',
    ];

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
