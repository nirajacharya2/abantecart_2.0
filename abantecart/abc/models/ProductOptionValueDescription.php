<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class AcProductOptionValueDescription
 *
 * @property int                    $product_option_value_id
 * @property int                    $language_id
 * @property int                    $product_id
 * @property string                 $name
 * @property string                 $grouped_attribute_names
 *
 * @property \abc\models\Product    $product
 * @property \abc\models\AcLanguage $language
 *
 * @package abc\models
 */
class ProductOptionValueDescription extends AModelBase
{
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'product_option_value_id' => 'int',
        'language_id'             => 'int',
        'product_id'              => 'int',
    ];

    protected $fillable = [
        'product_id',
        'name',
        'grouped_attribute_names',
    ];

    public function product()
    {
        return $this->belongsTo(\abc\models\Product::class, 'product_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
