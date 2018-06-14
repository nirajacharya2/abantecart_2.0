<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2018 Belavier Commerce LLC
 *
 * This source file is subject to Open Software License (OSL 3.0)
 * License details is bundled with this package in the file LICENSE.txt.
 * It is also available at this URL:
 * <http://www.opensource.org/licenses/OSL-3.0>
 *
 * UPGRADE NOTE:
 * Do not edit or add to this file if you wish to upgrade AbanteCart to newer
 * versions in the future. If you wish to customize AbanteCart for your
 * needs please refer to http://www.abantecart.com for more information.
 */
namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class ProductOptionDescription
 *
 * @property int $product_option_id
 * @property int $language_id
 * @property int $product_id
 * @property string $name
 * @property string $option_placeholder
 * @property string $error_text
 *
 * @property Product $product
 * @property Language $language
 * @property ProductOption $product_option
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
        'language_id',
        'product_id',
        'product_option_id',
        'name',
        'option_placeholder',
        'error_text',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
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
