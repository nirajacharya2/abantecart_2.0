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
 *
 */

namespace abc\models\catalog;

use abc\models\BaseModel;
use abc\models\locale\Language;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class CategoryDescription
 *
 * @property int $category_id
 * @property int $language_id
 * @property string $name
 * @property string $meta_keywords
 * @property string $meta_description
 * @property string $description
 *
 * @property Category $category
 * @property Language $language
 *
 * @package abc\models
 */
class CategoryDescription extends BaseModel
{
    protected $mainClassName = Category::class;
    protected $mainClassKey = 'category_id';

    protected $touches = ['category'];

    /**
     * @var string
     */
    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'category_id',
        'language_id',
    ];

    protected $casts = [
        'category_id'   => 'int',
        'language_id'   => 'int',
        'date_added'    => 'datetime',
        'date_modified' => 'datetime'
    ];

    protected $guarded = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'language_id',
        'name',
        'meta_keywords',
        'meta_description',
        'description',
    ];

    protected $rules = [
        /** @see validate() */
        'language_id'      => [
            'checks'   => [
                'integer',
            ],
            'messages' => [
                '*' => ['default_text' => 'Language ID is not Integer!'],
            ],
        ],
        'name'             => [
            'checks'   => [
                'string',
                'required',
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
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
