<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class ResourceLibrary
 *
 * @property int                                      $resource_id
 * @property int                                      $type_id
 * @property \Carbon\Carbon                           $date_added
 * @property \Carbon\Carbon                           $date_modified
 *
 * @property \abc\models\ResourceType                 $resource_type
 * @property \Illuminate\Database\Eloquent\Collection $resource_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $resource_maps
 *
 * @package abc\models
 */
class ResourceLibrary extends AModelBase
{
    protected $table = 'resource_library';
    protected $primaryKey = 'resource_id';
    public $timestamps = false;

    protected $casts = [
        'type_id' => 'int',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'type_id',
        'date_added',
        'date_modified',
    ];

    public function resource_type()
    {
        return $this->belongsTo(ResourceType::class, 'type_id');
    }

    public function resource_descriptions()
    {
        return $this->hasMany(ResourceDescription::class, 'resource_id');
    }

    public function resource_maps()
    {
        return $this->hasMany(ResourceMap::class, 'resource_id');
    }
}
