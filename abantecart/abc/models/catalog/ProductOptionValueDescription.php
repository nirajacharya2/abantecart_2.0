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
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ProductOptionValueDescription
 *
 * @property int $product_option_value_id
 * @property int $language_id
 * @property int $product_id
 * @property string $name
 * @property string $grouped_attribute_names
 *
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @property Product $product
 * @property Language $language
 *
 * @method static ProductOptionValueDescription find(int $id) ProductOptionValueDescription
 * @method static ProductOptionValueDescription create(array $attributes) ProductOptionValueDescription
 *
 * @package abc\models
 */
class ProductOptionValueDescription extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'product_option_value_id',
        'language_id',
        'product_id',
    ];
    protected $mainClassName = Product::class;
    protected $mainClassKey = 'product_id';

    protected $touches = ['product_option_value'];

    protected $casts = [
        'product_option_value_id' => 'int',
        'language_id'             => 'int',
        'product_id'              => 'int',
        'grouped_attribute_names' => 'serialized',
    ];

    /** @var array */
    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'product_option_value_id',
        'language_id',
        'product_id',
        'name',
        'grouped_attribute_names',
    ];

    protected $rules = [
        /** @see validate() */
        'product_option_value_id' => [
            'checks'   => [
                'integer',
                'required',
                'exists:product_option_values',
            ],
            'messages' => [
                '*' => ['default_text' => 'Product Option Value ID is not Integer or absent in product_option_values table!'],
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
                'max:1500',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'Product Option Name must be greater than 3 and less than 1500 characters!',
                ],
            ],
        ],
    ];

    public function setGroupedAttributeNamesAttribute($value)
    {
        if ($value !== null && !is_string($value)) {
            $this->attributes['grouped_attribute_names'] = serialize($value);
        }
    }

    public function product_option_value()
    {
        return $this->belongsTo(ProductOptionValue::class, 'product_option_value_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
