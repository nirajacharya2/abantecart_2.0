<?php

namespace abc\models\catalog;

use abc\models\BaseModel;
use abc\models\locale\Language;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ProductDescription
 *
 * @property int      $product_id
 * @property int      $language_id
 * @property string   $name
 * @property string   $meta_keywords
 * @property string   $meta_description
 * @property string   $description
 * @property string   $blurb
 * @property string $date_modified
 * @property string $date_added
 *
 * @property Product  $product
 * @property Language $language
 *
 * @package abc\models
 */
class ProductDescription extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'id';

    protected $mainClassName = Product::class;
    protected $mainClassKey = 'product_id';

    protected $primaryKeySet = [
        'product_id',
        'language_id',
    ];
    protected $casts = [
        'product_id'  => 'int',
        'language_id' => 'int',
        'description' => 'html',
        'name'        => 'html',
        'blurb'       => 'html',
    ];

    /** @var array */
    protected $dates = [
        'date_added',
        'date_modified',
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
                'exists:languages'
            ],
            'messages' => [
                '*' => ['default_text' => 'Language ID is not Integer or not presents in languages table!'],
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
                    'language_key'   => 'error_name',
                    'language_block' => 'catalog/product',
                    'default_text'   => 'Product Name must be greater than 3 and less than 255 characters!',
                    'section'        => 'admin',
                ],
            ],
        ],
        'meta_keywords' => [
            'checks'   => [
                'string',
                'max:255',
            ],
            'messages' => [
                '*' => [
                    'default_text'   => 'Meta keywords must be less than 255 characters!',
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
                    'default_text'   => 'Meta description must be less than 255 characters!',
                ],
            ],
        ],
        'description' => [
            'checks'   => [
                'string',
            ],
            'messages' => [
                '*' => [
                    'default_text'   => 'Description of product not set!',
                ],
            ],
        ],
        'blurb' => [
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

}
