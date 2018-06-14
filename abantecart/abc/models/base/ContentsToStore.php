<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class ContentsToStore
 *
 * @property int $content_id
 * @property int $store_id
 *
 * @property Content $content
 * @property Store $store
 *
 * @package abc\models
 */
class ContentsToStore extends AModelBase
{
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'content_id' => 'int',
        'store_id'   => 'int',
    ];

    public function content()
    {
        return $this->belongsTo(Content::class, 'content_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
}
