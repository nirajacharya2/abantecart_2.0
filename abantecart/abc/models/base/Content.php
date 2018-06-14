<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class Content
 *
 * @property int $content_id
 * @property int $parent_content_id
 * @property int $sort_order
 * @property int $status
 *
 * @property \Illuminate\Database\Eloquent\Collection $content_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $contents_to_stores
 *
 * @package abc\models
 */
class Content extends AModelBase
{
    public $timestamps = false;

    protected $casts = [
        'parent_content_id' => 'int',
        'sort_order'        => 'int',
        'status'            => 'int',
    ];

    protected $fillable = [
        'sort_order',
        'status',
    ];

    public function content_descriptions()
    {
        return $this->hasMany(ContentDescription::class, 'content_id');
    }

    public function contents_to_stores()
    {
        return $this->hasMany(ContentsToStore::class, 'content_id');
    }
}
