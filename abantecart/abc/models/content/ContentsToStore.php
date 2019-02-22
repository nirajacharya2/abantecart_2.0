<?php

namespace abc\models\content;

use abc\models\BaseModel;
use abc\models\system\Store;

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
class ContentsToStore extends BaseModel
{
    /**
     * @var string
     */
    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'category_id',
        'store_id'
    ];

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
