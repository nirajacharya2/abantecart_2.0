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
namespace abc\models\customer;

use abc\models\BaseModel;
use Carbon\Carbon;

/**
 * Class OnlineCustomer
 *
 * @property int $customer_id
 * @property string $ip
 * @property string $url
 * @property string $referer
 * @property Carbon $date_added
 *
 * @package abc\models
 */
class OnlineCustomer extends BaseModel
{
    protected $primaryKey = 'ip';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'customer_id' => 'int',
        'date_added' => 'datetime'
    ];

    protected $fillable = [
        'customer_id',
        'url',
        'referer',
        'date_added',
    ];
}
