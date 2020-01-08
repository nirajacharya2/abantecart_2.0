<?php

namespace abc\models\catalog;

use abc\models\BaseModel;
use abc\models\locale\Language;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ProductTag
 *
 * @property int $product_id
 * @property string $tag
 * @property int $language_id
 *
 * @property Product $product
 * @property Language $language
 *
 * @package abc\models
 */
class ProductTag extends BaseModel
{
    use SoftDeletes;
    protected $primaryKey = 'id';

    protected $mainClassName = Product::class;
    protected $mainClassKey = 'product_id';

    protected $touches = ['product'];
    protected $primaryKeySet = [
        'product_id',
        'language_id',
        'tag'
    ];

    protected $casts = [
        'product_id'  => 'int',
        'language_id' => 'int',
        'tag' => 'string',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
        'date_deleted',
    ];

    protected $fillable = [
            'product_id',
            'language_id',
            'tag'
    ];

    protected $rules = [
        /** @see validate() */
        'product_id'  => [
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
                '*' => ['default_text' => 'Language ID is not Integer or not presents in languages table!'],
            ],
        ],
        'tag'         => [
            'checks'   => [
                'string',
                'required',
                'max:32',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'Tag must be less than 32 characters!',
                ],
            ],
        ],
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
