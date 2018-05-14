<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class AcWeightClass
 *
 * @property int                                      $weight_class_id
 * @property float                                    $value
 * @property string                                   $iso_code
 * @property \Carbon\Carbon                           $date_added
 * @property \Carbon\Carbon                           $date_modified
 *
 * @property \Illuminate\Database\Eloquent\Collection $weight_class_descriptions
 *
 * @package abc\models
 */
class WeightClass extends AModelBase
{
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

    public function weight_class_descriptions()
    {
        return $this->hasMany(WeightClassDescription::class, 'weight_class_id');
    }
}
