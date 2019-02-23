<?php

namespace abc\models\system;

use abc\models\BaseModel;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['descriptions', 'rates'];

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

    public function descriptions()
    {
        return $this->hasMany(TaxClassDescription::class, 'tax_class_id');
    }

    public function rates()
    {
        return $this->hasMany(TaxRate::class, 'tax_class_id');
    }
}
