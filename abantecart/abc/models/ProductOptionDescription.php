<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class AcProductOptionDescription
 *
 * @property int                         $product_option_id
 * @property int                         $language_id
 * @property int                         $product_id
 * @property string                      $name
 * @property string                      $option_placeholder
 * @property string                      $error_text
 *
 * @property \abc\models\Product         $product
 * @property \abc\models\AcLanguage      $language
 * @property \abc\models\AcProductOption $product_option
 *
 * @package abc\models
 */
class ProductOptionDescription extends AModelBase
{
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'product_option_id' => 'int',
        'language_id'       => 'int',
        'product_id'        => 'int',
    ];

    protected $fillable = [
        'product_id',
        'name',
        'option_placeholder',
        'error_text',
    ];

    public function product()
    {
        return $this->belongsTo(\abc\models\Product::class, 'product_id');
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
