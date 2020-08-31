<?php

namespace abc\models\locale;

use abc\models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class LengthClassDescription
 *
 * @property int $length_class_id
 * @property int $language_id
 * @property string $title
 * @property string $unit
 *
 * @property Language $language
 *
 * @package abc\models
 */
class LengthClassDescription extends BaseModel
{

    use SoftDeletes;

    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'length_class_id',
        'language_id',
    ];
    public $timestamps = false;

    protected $casts = [
        'length_class_id' => 'int',
        'language_id'     => 'int',
    ];

    protected $fillable = [
        'title',
        'unit',
    ];
    protected $rules =[
        'title'=>[
            'checks'=>[
                'string',
                'between:2,32'
            ],
            'message'=>[
                'language_key'=> 'error_title',
                'language_block'=>'localisation/length_class',
                'default_text'=>'Length Title must be between 2 and 32 characters!',
                'section'=>'admin'
            ]
        ],
        'unit'=>[
            'checks'=>[
                'string',
                'between:1,4'
            ],
            'message'=>[
                'language_key'=> 'error_unit',
                'language_block'=>'localisation/country',
                'default_text'=>'Length Unit must be between 1 and 4 characters!',
                'section'=>'admin'
            ]
        ]
    ];
    public function length_class()
    {
        return $this->belongsTo(LengthClass::class, 'length_class_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
