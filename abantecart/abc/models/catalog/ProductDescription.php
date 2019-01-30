<?php

namespace abc\models\catalog;

use abc\models\BaseModel;

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
 *
 * @property Product $product
 * @property Language $language
 *
 * @package abc\models
 */
class ProductDescription extends BaseModel
{
    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;

    protected $primaryKeySet = [
        'product_id',
        'language_id'
    ];
    protected $casts = [
        'product_id'  => 'int',
        'language_id' => 'int',
    ];

    protected $fillable = [
        'product_id',
        'language_id',
        'name',
        'meta_keywords',
        'meta_description',
        'description',
        'blurb',
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
