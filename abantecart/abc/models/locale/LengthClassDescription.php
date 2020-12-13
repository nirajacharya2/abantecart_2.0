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
        'language_id' => 'int',
    ];

    protected $fillable = [
        'title',
        'unit',
        'id'
    ];
    protected $rules = [
        'id' => [
            'checks' => [
                'integer',
                'required',
                'sometimes',
                'min:1'
            ],
            'messages' => [
                'integer' => [
                    'language_key' => 'error_id',
                    'language_block' => 'localisation/length_class',
                    'default_text' => 'id must be integer!',
                    'section' => 'admin'
                ],
                'required' => [
                    'language_key' => 'error_language_id',
                    'language_block' => 'localisation/length_class',
                    'default_text' => 'id required!',
                    'section' => 'admin'
                ],
                'min' => [
                    'language_key' => 'error_language_id',
                    'language_block' => 'localisation/length_class',
                    'default_text' => 'id must be more 1!',
                    'section' => 'admin'
                ],
            ]
        ],
        'title' => [
            'checks' => [
                'string',
                'required',
                'sometimes',
                'min:2',
                'max:32'
            ],
            'messages' => [
                'min' => [
                    'language_key' => 'error_title',
                    'language_block' => 'localisation/length_class',
                    'default_text' => 'Length Title must be more 2 characters',
                    'section' => 'admin'
                ],
                'max' => [
                    'language_key' => 'error_title',
                    'language_block' => 'localisation/length_class',
                    'default_text' => 'Length Title must be no more than 32 characters',
                    'section' => 'admin'
                ],
                'required' => [
                    'language_key' => 'error_title',
                    'language_block' => 'localisation/length_class',
                    'default_text' => 'Length Title required!',
                    'section' => 'admin'
                ],
                'string' => [
                    'language_key' => 'error_name',
                    'language_block' => 'localisation/length_class',
                    'default_text' => 'Length Title must be string!',
                    'section' => 'admin'
                ],
            ]
        ],
        'unit' => [
            'checks' => [
                'string',
                'required',
                'sometimes',
                'min:1',
                'max:4'
            ],
            'messages' => [
                'min' => [
                    'language_key' => 'error_unit',
                    'language_block' => 'localisation/length_class',
                    'default_text' => 'Unit must be more 1 characters',
                    'section' => 'admin'
                ],
                'max' => [
                    'language_key' => 'error_unit',
                    'language_block' => 'localisation/length_class',
                    'default_text' => 'Unit must be no more than 4 characters',
                    'section' => 'admin'
                ],
                'required' => [
                    'language_key' => 'error_unit',
                    'language_block' => 'localisation/length_class',
                    'default_text' => 'Unit required!',
                    'section' => 'admin'
                ],
                'string' => [
                    'language_key' => 'error_unit',
                    'language_block' => 'localisation/length_class',
                    'default_text' => 'Unit must be string!',
                    'section' => 'admin'
                ],
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
