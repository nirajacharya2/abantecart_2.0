<?php

namespace abc\models\catalog;

use abc\models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ProductsRelated
 *
 * @property int $product_id
 * @property int $related_id
 *
 * @property Product $product
 *
 * @package abc\models
 */
class ProductsRelated extends BaseModel
{
    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'product_id',
        'related_id'
    ];

    protected $table = 'products_related';

    public $timestamps = false;

    protected $casts = [
        'product_id' => 'int',
        'related_id' => 'int',
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
                '*' => ['default_text' => ':attribute is not integer or absent in products table!'],
            ],
        ],
        'related_id' => [
            'checks'   => [
                'integer',
                'required',
                'exists:products,product_id',
            ],
            'messages' => [
                '*' => ['default_text' => ':attribute is not integer or absent in products table!'],
            ],
        ],
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
