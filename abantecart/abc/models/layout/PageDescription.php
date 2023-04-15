<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2023 Belavier Commerce LLC
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
namespace abc\models\layout;

use abc\models\BaseModel;
use abc\models\locale\Language;
use Carbon\Carbon;

/**
 * Class PageDescription
 *
 * @property int $page_id
 * @property int $language_id
 * @property string $name
 * @property string $title
 * @property string $seo_url
 * @property string $keywords
 * @property string $description
 * @property string $content
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @property Page $page
 * @property Language $language
 *
 * @package abc\models
 */
class PageDescription extends BaseModel
{

    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'page_id',
        'language_id',
    ];

    protected $casts = [
        'page_id'     => 'int',
        'language_id' => 'int'
    ];

    protected $fillable = [
        'name',
        'title',
        'seo_url',
        'keywords',
        'description',
        'content'
    ];

    protected $rules = [
        /** @see validate() */
        'language_id' => [
            'checks'   => [
                'int',
                'required',
                'sometimes'
            ],
            'messages' => [
                '*' => ['default_text' => 'Language ID is not integer!'],
            ],
        ],
        'name'        => [
            'checks'   => [
                'string',
                'required',
                'sometimes',
                'max:255'
            ],
            'messages' => [
                '*' => ['default_text' => 'Page Name is empty or length more than 255 chars!'],
            ],
        ],
        'title'       => [
            'checks'   => [
                'string',
                'required',
                'sometimes',
                'max:255'
            ],
            'messages' => [
                '*' => ['default_text' => 'Page Title is empty or length more than 255 chars!'],
            ],
        ],
        'seo_url'     => [
            'checks'   => [
                'string',
                'max:100'
            ],
            'messages' => [
                '*' => ['default_text' => 'SEO URL more than 100 chars!'],
            ],
        ],
        'description' => [
            'checks'   => [
                'string',
                'max:255'
            ],
            'messages' => [
                '*' => ['default_text' => 'Page Description more than 255 chars!'],
            ],
        ],
        'content'     => [
            'checks'   => [
                'string',
                'max:1500'
            ],
            'messages' => [
                '*' => ['default_text' => 'Page Content more than 1500 chars!'],
            ]
        ]
    ];

    public function page()
    {
        return $this->belongsTo(Page::class, 'page_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}