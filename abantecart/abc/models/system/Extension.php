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
use Carbon\Carbon;

/**
 * Class Extension
 *
 * @property int $extension_id
 * @property string $type
 * @property string $key
 * @property string $category
 * @property int $status
 * @property int $priority
 * @property string $version
 * @property string $license_key
 * @property Carbon $date_installed
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @package abc\models
 */
class Extension extends BaseModel
{
    protected $primaryKey = 'extension_id';
    public $timestamps = false;

    protected $casts = [
        'status'   => 'int',
        'priority' => 'int',
    ];

    protected $dates = [
        'date_installed',
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'type',
        'key',
        'category',
        'status',
        'priority',
        'version',
        'license_key',
        'date_installed',
        'date_added',
        'date_modified',
    ];
}
