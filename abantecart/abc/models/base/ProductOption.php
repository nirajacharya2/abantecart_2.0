<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class ProductOption
 *
 * @property int $product_option_id
 * @property int $attribute_id
 * @property int $product_id
 * @property int $group_id
 * @property int $sort_order
 * @property int $status
 * @property string $element_type
 * @property int $required
 * @property string $regexp_pattern
 * @property string $settings
 *
 * @property \abc\models\base\Product $product
 * @property \Illuminate\Database\Eloquent\Collection $product_option_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $product_option_values
 *
 * @package abc\models
 */
class ProductOption extends AModelBase
{
    protected $primaryKey = 'product_option_id';
    public $timestamps = false;

    protected $casts = [
        'attribute_id' => 'int',
        'product_id'   => 'int',
        'group_id'     => 'int',
        'sort_order'   => 'int',
        'status'       => 'int',
        'required'     => 'int',
    ];

    protected $fillable = [
        'attribute_id',
        'product_id',
        'group_id',
        'sort_order',
        'status',
        'element_type',
        'required',
        'regexp_pattern',
        'settings',
    ];

    public function product()
    {
        return $this->belongsTo(\abc\models\Product::class, 'product_id');
    }

    public function product_option_descriptions()
    {
        return $this->hasMany(ProductOptionDescription::class, 'product_option_id');
    }

    public function product_option_values()
    {
        return $this->hasMany(ProductOptionValue::class, 'product_option_id');
    }
}
