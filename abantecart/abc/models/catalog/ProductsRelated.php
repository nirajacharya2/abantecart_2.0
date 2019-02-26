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

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
