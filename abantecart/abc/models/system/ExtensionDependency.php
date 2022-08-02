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

/**
 * Class ExtensionDependency
 *
 * @property int $extension_id
 * @property int $extension_parent_id
 *
 * @package abc\models
 */
class ExtensionDependency extends BaseModel
{
    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'extension_id',
        'extension_parent_id'
    ];

    public $timestamps = false;

    protected $casts = [
        'extension_id'        => 'int',
        'extension_parent_id' => 'int',
    ];
}
