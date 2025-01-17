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
use abc\models\casts\Serialized;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class TaskDetail
 *
 * @property int $task_id
 * @property string $created_by
 * @property string $settings
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @package abc\models
 */
class TaskDetail extends BaseModel
{
    protected $primaryKey = 'task_id';

    protected $casts = [
        'created_by'    => 'int',
        'settings'      => Serialized::class,
        'date_added'    => 'datetime',
        'date_modified' => 'datetime'
    ];

    protected $fillable = [
        'task_id',
        'created_by',
        'settings'
    ];
}
