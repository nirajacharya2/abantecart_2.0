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

namespace abc\models\content;

use abc\models\BaseModel;
use abc\models\locale\Language;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ContentDescription
 *
 * @property int $content_id
 * @property int $language_id
 * @property string $name
 * @property string $title
 * @property string $description
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @property Content $content
 * @property Language $language
 *
 * @package abc\models
 */
class ContentDescription extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'id';
    protected $primaryKeySet = ['content_id', 'language_id'];

    public $timestamps = false;

    protected $casts = [
        'content_id'    => 'int',
        'language_id'   => 'int',
        'date_added'    => 'datetime',
        'date_modified' => 'datetime'
    ];

    protected $fillable = [
        'content_id',
        'language_id',
        'name',
        'title',
        'description',
        'meta_keywords',
        'meta_description',
        'content'
    ];

    protected $rules = [

        'content_id'       => [
            'checks'   => [
                'integer',
                'required',
                'sometimes'
            ],
            'messages' => [
                '*' => ['default_text' => 'Content ID is not Integer!'],
            ],
        ],
        'language_id'      => [
            'checks'   => [
                'integer',
                'required'
            ],
            'messages' => [
                '*' => ['default_text' => 'Language ID is not Integer!'],
            ],
        ],
        'name'             => [
            'checks'   => [
                'required',
                'sometimes',
                'string',
                'max:255',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be a string between 1 abd 255 characters!',
                ],
            ],
        ],
        'title'            => [
            'checks'   => [
                'string',
                'max:255',
                'required',
                'sometimes',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be a string between 1 abd 255 characters!',
                ],
            ],
        ],
        'description'      => [
            'checks'   => [
                'string',
                'max:255',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be a string between 1 abd 255 characters!',
                ],
            ],
        ],
        'meta_keywords'    => [
            'checks'   => [
                'string',
                'max:255',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be a string less than 255 characters!',
                ],
            ],
        ],
        'meta_description' => [
            'checks'   => [
                'string',
                'max:255',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be a string less than 255 characters!',
                ],
            ],
        ],
        'content'          => [
            'checks'   => [
                'required',
                'sometimes',
                'string',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is required!',
                ],
            ],
        ],
    ];

    public function content()
    {
        return $this->belongsTo(Content::class, 'content_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
