<?php

namespace abc\models;

/**
 * Class AntMessage
 *
 * @property string         $id
 * @property int            $priority
 * @property \Carbon\Carbon $start_date
 * @property \Carbon\Carbon $end_date
 * @property \Carbon\Carbon $viewed_date
 * @property int            $viewed
 * @property string         $title
 * @property string         $description
 * @property string         $html
 * @property string         $url
 * @property string         $language_code
 * @property \Carbon\Carbon $date_modified
 *
 * @package abc\models
 */
class AntMessage extends BaseModel
{
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'priority' => 'int',
        'viewed'   => 'int',
    ];

    protected $dates = [
        'start_date',
        'end_date',
        'viewed_date',
        'date_modified',
    ];

    protected $fillable = [
        'priority',
        'start_date',
        'end_date',
        'viewed_date',
        'viewed',
        'title',
        'description',
        'html',
        'url',
        'date_modified',
    ];
}
