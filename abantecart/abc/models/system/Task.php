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
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Task
 *
 * @property int $task_id
 * @property string $name
 * @property int $starter
 * @property int $status
 * @property Carbon $start_time
 * @property Carbon $last_time_run
 * @property int $progress
 * @property int $last_result
 * @property int $run_interval
 * @property int $max_execution_time
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @method static Task create(array $data)
 *
 * @package abc\models
 */
class Task extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'task_id';
    public $timestamps = false;

    protected $casts = [
        'starter'            => 'int',
        'status'             => 'int',
        'progress'           => 'int',
        'last_result'        => 'int',
        'run_interval'       => 'int',
        'max_execution_time' => 'int',
        'start_time'         => 'datetime',
        'last_time_run'      => 'datetime',
        'date_added'         => 'datetime',
        'date_modified'      => 'datetime'
    ];

    protected $fillable = [
        'name',
        'starter',
        'status',
        'start_time',
        'last_time_run',
        'progress',
        'last_result',
        'run_interval',
        'max_execution_time',
        'date_added',
        'date_modified',
    ];
}
