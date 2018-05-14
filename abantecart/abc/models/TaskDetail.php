<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class TaskDetail
 *
 * @property int            $task_id
 * @property string         $created_by
 * @property string         $settings
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @package abc\models
 */
class TaskDetail extends AModelBase
{
    protected $primaryKey = 'task_id';
    public $timestamps = false;

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'created_by',
        'settings',
        'date_added',
        'date_modified',
    ];
}
