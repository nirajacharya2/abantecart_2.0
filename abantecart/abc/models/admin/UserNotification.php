<?php

namespace abc\models;

use abc\models\base\Store;

/**
 * Class AcUserNotification
 *
 * @property int                 $user_id
 * @property int                 $store_id
 * @property bool                $section
 * @property string              $sendpoint
 * @property string              $protocol
 * @property string              $uri
 * @property \Carbon\Carbon      $date_added
 * @property \Carbon\Carbon      $date_modified
 *
 * @property \abc\models\User  $user
 * @property Store $store
 *
 * @package abc\models
 */
class UserNotification extends AModelBase
{
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'user_id'  => 'int',
        'store_id' => 'int',
        'section'  => 'bool',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'uri',
        'date_added',
        'date_modified',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
}
