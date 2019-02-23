<?php

namespace abc\models\locale;

use abc\models\BaseModel;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class WeightClass
 *
 * @property int $weight_class_id
 * @property float $value
 * @property string $iso_code
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @property \Illuminate\Database\Eloquent\Collection $weight_class_descriptions
 *
 * @package abc\models
 */
class WeightClass extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['descriptions'];

    protected $primaryKey = 'weight_class_id';
    public $timestamps = false;

    protected $casts = [
        'value' => 'float',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'value',
        'date_added',
        'date_modified',
    ];

    public function description()
    {
        return $this->hasOne(WeightClassDescription::class, 'weight_class_id')
                    ->where('language_id', $this->registry->get('language')->getContentLanguageID());
    }

    public function descriptions()
    {
        return $this->hasMany(WeightClassDescription::class, 'weight_class_id');
    }
}
