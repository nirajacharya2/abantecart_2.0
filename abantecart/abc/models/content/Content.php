<?php

namespace abc\models\content;

use abc\models\BaseModel;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletes;

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
class Content extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['descriptions', 'stores'];
    public $timestamps = false;

    protected $primaryKey = 'content_id';
    protected $casts = [
        'parent_content_id' => 'int',
        'sort_order'        => 'int',
        'status'            => 'int',
    ];

    protected $fillable = [
        'sort_order',
        'status',
    ];

    public function descriptions()
    {
        return $this->hasMany(ContentDescription::class, 'content_id');
    }

    public function stores()
    {
        return $this->hasMany(ContentsToStore::class, 'content_id');
    }
}
