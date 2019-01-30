<?php

namespace abc\models\catalog;

use abc\models\BaseModel;

/**
 * Class ResourceMap
 *
 * @property int $resource_id
 * @property string $object_name
 * @property int $object_id
 * @property bool $default
 * @property int $sort_order
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @property ResourceLibrary $resource_library
 *
 * @package abc\models
 */
class ResourceMap extends BaseModel
{
    protected $table = 'resource_map';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'resource_id' => 'int',
        'object_id'   => 'int',
        'default'     => 'bool',
        'sort_order'  => 'int',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'default',
        'sort_order',
        'date_added',
        'date_modified',
    ];

    public function resource_library()
    {
        return $this->belongsTo(ResourceLibrary::class, 'resource_id');
    }
}
