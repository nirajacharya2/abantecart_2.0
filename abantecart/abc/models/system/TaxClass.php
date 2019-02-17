<?php

namespace abc\models\system;

use abc\models\BaseModel;

/**
 * Class TaxClass
 *
 * @property int $tax_class_id
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @property \Illuminate\Database\Eloquent\Collection $tax_class_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $tax_rates
 *
 * @package abc\models
 */
class TaxClass extends BaseModel
{
    protected $primaryKey = 'tax_class_id';
    public $timestamps = false;

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'date_added',
        'date_modified',
    ];

    public function description()
    {
        return $this->hasOne(TaxClassDescription::class, 'tax_class_id')
            ->where('language_id', $this->registry->get('language')->getContentLanguageID());
    }

    public function tax_class_descriptions()
    {
        return $this->hasMany(TaxClassDescription::class, 'tax_class_id');
    }

    public function tax_rates()
    {
        return $this->hasMany(TaxRate::class, 'tax_class_id');
    }
}
