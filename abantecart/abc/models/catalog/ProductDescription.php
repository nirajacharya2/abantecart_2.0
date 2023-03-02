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
use abc\models\casts\Html;
use abc\models\locale\Language;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ProductDescription
 *
 * @property int $product_id
 * @property int $language_id
 * @property string $name
 * @property string $meta_keywords
 * @property string $meta_description
 * @property string $description
 * @property string $blurb
 * @property Carbon $date_modified
 * @property Carbon $date_added
 *
 * @property Product $product
 * @property Language $language
 *
 * @package abc\models
 */
class ProductDescription extends BaseModel
{
    protected $primaryKey = 'id';

    protected $mainClassName = Product::class;
    protected $mainClassKey = 'product_id';

    protected $primaryKeySet = [
        'product_id',
        'language_id',
    ];
    protected $casts = [
        'product_id'    => 'int',
        'language_id'   => 'int',
        'description'   => Html::class,
        'name'          => Html::class,
        'blurb'         => Html::class,
        'date_added'    => 'datetime',
        'date_modified' => 'datetime'
    ];

    protected $touches = ['product'];

    protected $fillable = [
        'product_id',
        'language_id',
        'name',
        'meta_keywords',
        'meta_description',
        'description',
        'blurb',
    ];

    protected $rules = [
        /** @see validate() */
        'product_id'       => [
            'checks'   => [
                'integer',
                'required',
                'exists:products',
            ],
            'messages' => [
                '*' => ['default_text' => 'Product ID is not Integer or absent in products table!'],
            ],
        ],
        'language_id'      => [
            'checks'   => [
                'integer',
                'required',
                'exists:languages'
            ],
            'messages' => [
                '*' => ['default_text' => 'Language ID is not Integer or not presents in languages table!'],
            ],
        ],
        'name'             => [
            'checks'   => [
                'string',
                'sometimes',
                'required',
                'between:3,255',
            ],
            'messages' => [
                '*' => [
                    'language_key'   => 'error_name',
                    'language_block' => 'catalog/product',
                    'default_text'   => 'Product Name must be greater than 3 and less than 255 characters!',
                    'section'        => 'admin',
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
                    'default_text' => 'Meta keywords must be less than 255 characters!',
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
                    'default_text' => 'Meta description must be less than 255 characters!',
                ],
            ],
        ],
        'description'      => [
            'checks'   => [
                'string',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'Description of product not set!',
                ],
            ],
        ],
        'blurb'            => [
            'checks'   => [
                'string',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'Blurb must be less than 1500 characters!',
                ],
            ],
        ]
    ];

    /**
     * @param string $value
     *
     * @return string
     */
    public function getNameAttribute($value)
    {
        return $value === '' ? 'n/a' : $value;
    }

    /**
     * @return BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * @return BelongsTo
     */
    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }

}
