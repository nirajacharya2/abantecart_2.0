<?php

namespace abc\models\locale;

use abc\models\BaseModel;
use abc\models\system\TaxRate;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Location
 *
 * @property int $location_id
 * @property string $name
 * @property string $description
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @property \Illuminate\Database\Eloquent\Collection $tax_rates
 * @property \Illuminate\Database\Eloquent\Collection $zones_to_locations
 *
 * @package abc\models
 */
class Location extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['tax_rates', 'zones_to_locations'];

    protected $primaryKey = 'location_id';
    public $timestamps = false;

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'name',
        'description',
        'date_added',
        'date_modified',
    ];
    protected $rules = [
        'name'=>[
            'checks'=>[
                'string',
                'between:2,32',
                'required',
                'sometimes'
            ],
            'messages'=>[
                'language_key'=> 'error_name',
                'language_block'=>'localisation/location',
                'default_text'=>'Name must be between 2 and 32 characters!',
                'section'=>'admin'
            ]
        ],
        'description'=>[
            'checks'=>[
                'string',
                'between:2,255',
                'required',
                'sometimes'
            ],
            'messages'=>[
                'language_key'=> 'error_description',
                'language_block'=>'localisation/location',
                'default_text'=>'Description Name must be between 2 and 255 characters!',
                'section'=>'admin'
            ]
        ]
    ];
    public function tax_rates()
    {
        return $this->hasMany(TaxRate::class, 'location_id');
    }

    public function zones_to_locations()
    {
        return $this->hasMany(ZonesToLocation::class, 'location_id');
    }
}
