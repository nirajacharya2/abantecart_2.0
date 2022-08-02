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
namespace abc\models\system;

use abc\models\BaseModel;

class AuditUser extends BaseModel
{
    const USER_TYPES = [
        'root'       => 1,
        'system'     => 2,
        'storefront' => 3,
        'admin'      => 4,
    ];
    public $timestamps = false;

    protected $fillable = [
        'user_type',
        'user_id',
        'name',
    ];

    public static $auditingEnabled = false;
    public static $auditEvents = [];

    public static function getUserTypeId($type)
    {
        return self::USER_TYPES[$type] ?? 1;
    }

}
