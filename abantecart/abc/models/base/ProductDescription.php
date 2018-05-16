<?php

namespace abc\models\base;

use abc\models\AModelBase;

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
 * @property \abc\models\base\Product $product
 * @property \abc\models\base\Language $language
 *
 * @package abc\models
 */
class ProductDescription extends AModelBase
{
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'product_id'  => 'int',
        'language_id' => 'int',
    ];

    protected $fillable = [
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
