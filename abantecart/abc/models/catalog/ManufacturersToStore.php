<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2022 Belavier Commerce LLC
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
use abc\models\system\Store;

/**
 * Class ManufacturersToStore
 *
 * @property int $manufacturer_id
 * @property int $store_id
 *
 * @property Manufacturer $manufacturer
 * @property Store $store
 *
 * @package abc\models
 */
class ManufacturersToStore extends BaseModel
{
    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'manufacturer_id',
        'store_id'
    ];

    public $timestamps = false;

    protected $casts = [
        'manufacturer_id' => 'int',
        'store_id'        => 'int',
    ];

    public function manufacturer()
    {
        return $this->belongsTo(Manufacturer::class, 'manufacturer_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
}
