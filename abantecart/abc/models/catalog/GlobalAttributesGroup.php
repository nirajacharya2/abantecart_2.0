<?php

namespace abc\models\catalog;

use abc\models\BaseModel;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class GlobalAttributesGroup
 *
 * @property int $attribute_group_id
 * @property int $sort_order
 * @property int $status
 *
 * @package abc\models
 */
class GlobalAttributesGroup extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;
    const DELETED_AT = 'date_deleted';
    protected $cascadeDeletes = ['descriptions'];
    protected $primaryKey = 'attribute_group_id';
    public $timestamps = false;

    protected $casts = [
        'sort_order' => 'int',
        'status'     => 'int',
    ];

    protected $fillable = [
        'sort_order',
        'status',
    ];

    public function object_types()
    {
        return $this->belongsToMany(GlobalAttributesGroup::class, 'global_attribute_group_to_object_type',
            'attribute_group_id', 'object_type_id');
    }

    public function global_attributes()
    {
        return $this->hasMany(GlobalAttribute::class, 'attribute_group_id');
    }

    public function description()
    {
        return $this->hasOne(GlobalAttributesGroupsDescription::class, 'attribute_group_id')
            ->where('language_id', $this->registry->get('language')->getContentLanguageID());
    }

    public function descriptions()
    {
        return $this->hasMany(GlobalAttributesGroupsDescription::class, 'attribute_group_id');
    }

}
