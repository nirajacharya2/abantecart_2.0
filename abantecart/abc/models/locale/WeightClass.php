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
        'weight_class_id',
        'value',
        'date_added',
        'date_modified',
    ];
    protected $rules = [
        'weight_class_id' => [
            'checks' => [
                'integer',
                'required',
                'sometimes',
                'min:1'
            ],
            'messages' => [
                'integer' => [
                    'language_key' => 'error_weight_class_id',
                    'language_block' => 'localisation/weight_class',
                    'default_text' => 'weight class id must be integer!',
                    'section' => 'admin'
                ],
                'required' => [
                    'language_key' => 'error_weight_class_id',
                    'language_block' => 'localisation/weight_class',
                    'default_text' => 'weight class id required!',
                    'section' => 'admin'
                ],
                'min' => [
                    'language_key' => 'error_weight_class_id',
                    'language_block' => 'localisation/weight_class',
                    'default_text' => 'weight class id must be more 1!',
                    'section' => 'admin'
                ],
            ]
        ],
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
