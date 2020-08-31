<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2018 Belavier Commerce LLC
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

namespace abc\models\catalog;

use abc\models\BaseModel;
use abc\models\locale\Language;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ProductOptionDescription
 *
 * @property int $product_option_id
 * @property int $language_id
 * @property int $product_id
 * @property string $name
 * @property string $option_placeholder
 * @property string $error_text
 *
 * @property Product $product
 * @property Language $language
 * @property ProductOption $product_option
 *
 * @method static ProductOptionDescription create(array $attributes) ProductOptionDescription
 *
 *
 * @package abc\models
 */
class ProductOptionDescription extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'product_option_id',
        'language_id',
    ];

    protected $mainClassName = Product::class;
    protected $mainClassKey = 'product_id';

    protected $touches = ['product_option'];

    protected $casts = [
        'product_option_id' => 'int',
        'language_id'       => 'int',
        'product_id'        => 'int',
    ];

    /** @var array */
    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'language_id',
        'product_id',
        'product_option_id',
        'name',
        'option_placeholder',
        'error_text',
    ];

    protected $rules = [
        /** @see validate() */
        'product_option_id' => [
            'checks'   => [
                'integer',
                'required',
                'exists:product_options',
            ],
            'messages' => [
                '*' => ['default_text' => 'Product Option ID is not Integer or absent in product_options table!'],
            ],
        ],

        'product_id' => [
            'checks'   => [
                'integer',
                'required',
                'exists:products',
            ],
            'messages' => [
                '*' => ['default_text' => 'Product ID is not Integer or absent in products table!'],
            ],
        ],

        'language_id' => [
            'checks'   => [
                'integer',
                'required',
                'exists:languages',
            ],
            'messages' => [
                '*' => ['default_text' => 'Language ID is not Integer or absent in languages table!'],
            ],
        ],

        'name' => [
            'checks'   => [
                'string',
                'sometimes',
                'required',
                'between:3,255',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'Product Option Name must be greater than 3 and less than 255 characters!',
                ],
            ],
        ],

        'option_placeholder' => [
            'checks'   => [
                'string',
                'max:255',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'Product Option Placeholder must be less than 255 characters!',
                ],
            ],
        ],

        'error_text' => [
            'checks'   => [
                'string',
                'max:255',
                'nullable',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'Product Option Error Text must be less than 255 characters!',
                ],
            ],
        ],

    ];

    public function getNameAttribute($value)
    {
        return $value === '' ? 'n/a' : $value;
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }

    public function product_option()
    {
        return $this->belongsTo(ProductOption::class, 'product_option_id');
    }
}
