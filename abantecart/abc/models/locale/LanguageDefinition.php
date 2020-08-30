<?php

namespace abc\models\locale;

use abc\models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class LanguageDefinition
 *
 * @property int $language_definition_id
 * @property int $language_id
 * @property bool $section
 * @property string $block
 * @property string $language_key
 * @property string $language_value
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @property Language $language
 *
 * @package abc\models
 */
class LanguageDefinition extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'language_definition_id';
    public $timestamps = false;

    protected $casts = [
        'language_id' => 'int',
        'section' => 'bool',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'language_definition_id',
        'language_value',
        'date_added',
        'date_modified',
    ];
    protected $rules = [
        'language_definition_id' => [
            'checks' => [
                'integer',
                'required',
                'sometimes',
                'min:1'
            ],
            'messages' => [
                '*' => ['default_text' => 'language_definition_id is not integer']
            ]
        ],
        'language_value' => [
            'checks' => [
                'string',
                'required'
            ],
            'messages' => [
                'language_key' => 'error_language_value',
                'language_block' => 'localisation/language_definitions',
                'default_text' => 'Locale required',
                'section' => 'admin'
            ]
        ]
    ];

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
