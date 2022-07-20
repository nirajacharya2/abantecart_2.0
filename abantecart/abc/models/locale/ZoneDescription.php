<?php

namespace abc\models\locale;

use abc\models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ZoneDescription
 *
 * @property int $zone_id
 * @property int $language_id
 * @property string $name
 *
 * @property Zone $zone
 * @property Language $language
 *
 * @package abc\models
 */
class ZoneDescription extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'zone_id',
        'language_id',
    ];

    protected $touches = ['zone'];
    protected $casts = [
        'zone_id' => 'int',
        'language_id' => 'int',
    ];

    protected $fillable = [
        'name',
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
                    'language_block' => 'localisation/zone',
                    'default_text' => 'id must be integer!',
                    'section' => 'admin'
                ],
                'required' => [
                    'language_key' => 'error_id',
                    'language_block' => 'localisation/zone',
                    'default_text' => 'id required!',
                    'section' => 'admin'
                ],
                'min' => [
                    'language_key' => 'error_id',
                    'language_block' => 'localisation/zone',
                    'default_text' => 'id must be more 1!',
                    'section' => 'admin'
                ],
            ]
        ],
        'name' => [
            'checks' => [
                'string',
                'required',
                'sometimes',
                'min:2',
                'max:128'
            ],
            'messages' => [
                'min' => [
                    'language_key' => 'error_name',
                    'language_block' => 'localisation/zone',
                    'default_text' => 'Name must be more 2 characters',
                    'section' => 'admin'
                ],
                'max' => [
                    'language_key' => 'error_name',
                    'language_block' => 'localisation/zone',
                    'default_text' => 'Name must be no more than 255 characters',
                    'section' => 'admin'
                ],
                'required' => [
                    'language_key' => 'error_name',
                    'language_block' => 'localisation/zone',
                    'default_text' => 'Name required!',
                    'section' => 'admin'
                ],
                'string' => [
                    'language_key' => 'error_name',
                    'language_block' => 'localisation/zone',
                    'default_text' => 'Name must be string!',
                    'section' => 'admin'
                ],
            ]
        ]
    ];

    public function zone()
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
