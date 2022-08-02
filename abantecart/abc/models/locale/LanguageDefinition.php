<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2022 Belavier Commerce LLC
 *
 * This source file is subject to Open Software License (OSL 3.0)
 * License details is bundled with this package in the file LICENSE.txt.
 * It is also available at this URL:
 * <http://www.opensource.org/licenses/OSL-3.0>
 *
 * UPGRADE NOTE:
 * Do not edit or add to this file if you wish to upgrade AbanteCart to newer
 * versions in the future. If you wish to customize AbanteCart for your
 * needs please refer to http://www.abantecart.com for more information.
 */
namespace abc\models\locale;

use abc\models\BaseModel;
use Carbon\Carbon;
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
 * @property Carbon $date_added
 * @property Carbon $date_modified
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
                'integer' => [
                    'language_key' => 'error_language_definition_id',
                    'language_block' => 'localisation/language_definitions',
                    'default_text' => 'language definition id must be integer!',
                    'section' => 'admin'
                ],
                'required' => [
                    'language_key' => 'error_language_definition_id',
                    'language_block' => 'localisation/language',
                    'default_text' => 'language definition id required!',
                    'section' => 'admin'
                ],
                'min' => [
                    'language_key' => 'error_language_definition_id',
                    'language_block' => 'localisation/language',
                    'default_text' => 'language definition id must be more 1!',
                    'section' => 'admin'
                ],
            ],
        ],
        'language_value' => [
            'checks' => [
                'string',
                'sometimes',
                'required'
            ],
            'messages' => [
                'required' => [
                    'language_key' => 'error_language_value',
                    'language_block' => 'localisation/language',
                    'default_text' => 'language value required!',
                    'section' => 'admin'
                ],
                'string' => [
                    'language_key' => 'error_language_value',
                    'language_block' => 'localisation/language',
                    'default_text' => 'language value must be string!',
                    'section' => 'admin'
                ],
            ]
        ]
    ];

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
