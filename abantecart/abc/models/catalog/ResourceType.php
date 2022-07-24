<?php

namespace abc\models\catalog;

use abc\models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ResourceType
 *
 * @property int $type_id
 * @property string $type_name
 * @property string $default_directory
 * @property string $default_icon
 * @property string $file_types
 * @property bool $access_type
 *
 * @property \Illuminate\Database\Eloquent\Collection $resource_libraries
 *
 * @method static ResourceType find(int $resource_id) ResourceType
 * @method static ResourceType select(mixed $select) Builder
 *
 * @package abc\models
 */
class ResourceType extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'type_id';
    public $timestamps = false;

    protected $casts = [
        'access_type' => 'bool',
    ];

    protected $fillable = [
        'type_name',
        'default_directory',
        'default_icon',
        'file_types',
        'access_type',
    ];

    public function resource_libraries()
    {
        return $this->hasMany(ResourceLibrary::class, 'type_id');
    }
}
