<?php

namespace abc\models\locale;

use abc\models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ZonesToLocation
 *
 * @property int $zone_to_location_id
 * @property int $country_id
 * @property int $zone_id
 * @property int $location_id
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @property Zone $zone
 * @property Country $country
 * @property Location $location
 *
 * @package abc\models
 */
class ZonesToLocation extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'zone_to_location_id';
    public $timestamps = false;

    protected $casts = [
        'country_id'  => 'int',
        'zone_id'     => 'int',
        'location_id' => 'int',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'country_id',
        'zone_id',
        'location_id',
        'date_added',
        'date_modified',
    ];
    protected $rules =[
        'country_id'=>[
            'checks'=>[
             'required',
                'integer'
            ],
            'messages'=>[
                '*'=>['default_text'=>'country_id is not integer']
            ]
        ],
        'zone_id'=>[
            'checks'=>[
                'required',
                'integer',
                'sometimes'
            ],
            'messages'=>[
                '*'=>['default_text'=>'zone_id is not integer']
            ]
        ],
        'location_id'=>[
            'checks'=>[
                'required',
                'integer'
            ],
            'messages'=>[
                '*'=>['default_text'=>'location_id is not integer']
            ]
        ]
    ];

    public function zone()
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}
