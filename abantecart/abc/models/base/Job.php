<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class AcJob
 *
 * @property int $job_id
 * @property string $job_name
 * @property int $status
 * @property string $configuration
 * @property \Carbon\Carbon $start_time
 * @property \Carbon\Carbon $last_time_run
 * @property int $last_result
 * @property int $actor_type
 * @property int $actor_id
 * @property string $actor_name
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @package abc\models
 */
class Job extends AModelBase
{
    public $timestamps = false;

    protected $casts = [
        'status'      => 'int',
        'last_result' => 'int',
        'actor_type'  => 'int',
        'actor_id'    => 'int',
    ];

    protected $dates = [
        'start_time',
        'last_time_run',
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'status',
        'configuration',
        'start_time',
        'last_time_run',
        'last_result',
        'actor_type',
        'actor_id',
        'actor_name',
        'date_added',
        'date_modified',
    ];
}
