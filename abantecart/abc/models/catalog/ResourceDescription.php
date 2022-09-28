<?php

namespace abc\models\catalog;

use abc\models\BaseModel;
use abc\models\locale\Language;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ResourceDescription
 *
 * @property int $resource_id
 * @property int $language_id
 * @property string $name
 * @property string $title
 * @property string $description
 * @property string $resource_path
 * @property string $resource_code
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @property ResourceLibrary $resource_library
 * @property Language $language
 *
 * @package abc\models
 */
class ResourceDescription extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'resource_id',
        'language_id',
    ];

    public $timestamps = false;

    protected $casts = [
        'resource_id'   => 'int',
        'language_id'   => 'int',
        'date_added'    => 'datetime',
        'date_modified' => 'datetime'
    ];

    protected $fillable = [
        'name',
        'title',
        'description',
        'resource_path',
        'resource_code',
        'date_added',
        'date_modified',
    ];

    public function resource_library()
    {
        return $this->belongsTo(ResourceLibrary::class, 'resource_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
