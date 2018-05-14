<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class EncryptionKey
 *
 * @property int    $key_id
 * @property string $key_name
 * @property int    $status
 * @property string $comment
 *
 * @package abc\models
 */
class EncryptionKey extends AModelBase
{
    protected $primaryKey = 'key_id';
    public $timestamps = false;

    protected $casts = [
        'status' => 'int',
    ];

    protected $fillable = [
        'key_name',
        'status',
        'comment',
    ];
}
