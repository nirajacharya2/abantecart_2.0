<?php

namespace abc\models\base;

use abc\models\BaseModel;

/**
 * Class EncryptionKey
 *
 * @property int $key_id
 * @property string $key_name
 * @property int $status
 * @property string $comment
 *
 * @package abc\models
 */
class EncryptionKey extends BaseModel
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
