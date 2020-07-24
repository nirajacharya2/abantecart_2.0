<?php

namespace abc\models\locale;

use abc\models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class CountryDescription
 *
 * @property int $country_id
 * @property int $language_id
 * @property string $name
 *
 * @property Country $country
 * @property Language $language
 *
 * @package abc\models
 */
class CountryDescription extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'country_id',
        'language_id',
    ];

    protected $touches = ['country'];

    protected $casts = [
        'country_id'  => 'int',
        'language_id' => 'int',
    ];

    protected $fillable = [
        'name',
        'language_id',
    ];
    protected $rules =[
       'name'=>[
           'checks'=>[
                'string',
               'between:2,128'
           ],
           'messages'=>[
               'language_key'=> 'error_name',
                'language_block'=>'localisation/country',
                'default_text'=>'Country Name must be between 2 and 128 characters!',
                'section'=>'admin'
           ],
       ],
        'language_id'=>[
            'checks'=>[
                'integer',
                'required'
            ],
            'messages'=>[
                '*'=>['default_text'=>'language_id is not integer']
            ],
        ]
    ];
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
