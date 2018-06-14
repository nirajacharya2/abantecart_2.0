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
 *
 */

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
 * @property ProductOptionValue $option_values
 *
 * @property Product $product
 * @property \Illuminate\Database\Eloquent\Collection $product_option_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $product_option_values
 *
 * @package abc\models
 */
class ProductOption extends AModelBase
{
    /**
     * @var string
     */
    protected $primaryKey = 'product_option_id';
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $casts = [
        'attribute_id' => 'int',
        'product_id'   => 'int',
        'group_id'     => 'int',
        'sort_order'   => 'int',
        'status'       => 'int',
        'required'     => 'int',
    ];

    /**
     * @var array
     */
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

    /**
     * @return mixed
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * @return mixed
     */
    public function option_descriptions()
    {
        return $this->hasMany(ProductOptionDescription::class, 'product_option_id');
    }

    /**
     * @return mixed
     */
    public function option_values()
    {
        return $this->hasMany(ProductOptionValue::class, 'product_option_id');
    }

    /**
     * @return false|mixed
     */
    public function getAllData() {
        $cache_key = 'product.alldata.'.$this->getKey();
        $data = $this->cache->pull($cache_key);
        if ($data === false) {
            $this->load('option_descriptions');
            $data = $this->toArray();
            foreach ($this->option_values as $optionValue) {
                $data['option_values'][] = $optionValue->getAllData();
            }
            $this->cache->push($cache_key, $data);
        }
        return $data;
    }
}
