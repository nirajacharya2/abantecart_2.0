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

namespace abc\models\catalog;

use abc\models\BaseModel;

/**
 * Class ProductType
 *
 * @package abc\models
 */
class ProductType extends BaseModel
{
    protected $primaryKey = 'product_type_id';
    public $timestamps = false;

    protected $casts = [
        'banner_id'   => 'int',
        'language_id' => 'int',
    ];

    protected $fillable = [
        'stage_id'
    ];


    public function product_type_descriptions()
    {
        return $this->hasMany(ProductDescription::class, 'product_type_id');
    }

    public function global_attribute_groups()
    {
        return $this->belongsToMany(GlobalAttributesGroup::class, 'global_attribute_group_to_product_type',
            'product_type_id', 'attribute_group_id');
    }
}