<?php

namespace abc\models\base;

use abc\models\AModelBase;

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
class ProductTag extends AModelBase
{
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'product_id'  => 'int',
        'language_id' => 'int',
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
