<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class Task
 *
 * @property int $task_id
 * @property string $name
 * @property int $starter
 * @property int $status
 * @property \Carbon\Carbon $start_time
 * @property \Carbon\Carbon $last_time_run
 * @property int $progress
 * @property int $last_result
 * @property int $run_interval
 * @property int $max_execution_time
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @package abc\models
 */
class Task extends AModelBase
{
    protected $primaryKey = 'task_id';
    public $timestamps = false;

    protected $casts = [
        'starter'            => 'int',
        'status'             => 'int',
        'progress'           => 'int',
        'last_result'        => 'int',
        'run_interval'       => 'int',
        'max_execution_time' => 'int',
    ];

    protected $dates = [
        'start_time',
        'last_time_run',
        'date_added',
        'date_modified',
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
