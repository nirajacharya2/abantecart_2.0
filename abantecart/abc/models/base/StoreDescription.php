<?php

namespace abc\models\base;

use abc\models\BaseModel;

/**
 * Class StoreDescription
 *
 * @property int $store_id
 * @property int $language_id
 * @property string $description
 * @property string $title
 * @property string $meta_description
 * @property string $meta_keywords
 *
 * @property Store $store
 * @property Language $language
 *
 * @package abc\models
 */
class StoreDescription extends BaseModel
{
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'store_id'    => 'int',
        'language_id' => 'int',
    ];

    protected $fillable = [
        'description',
        'title',
        'meta_description',
        'meta_keywords',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
