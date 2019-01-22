<?php

namespace abc\models\base;

use abc\models\BaseModel;

/**
 * Class GlobalAttribute
 *
 * @property int $attribute_id
 * @property int $attribute_parent_id
 * @property int $attribute_group_id
 * @property int $attribute_type_id
 * @property string $element_type
 * @property int $sort_order
 * @property int $required
 * @property string $settings
 * @property int $status
 * @property string $regexp_pattern
 *
 * @property \Illuminate\Database\Eloquent\Collection $global_attributes_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $global_attributes_value_descriptions
 * @property \Illuminate\Database\Eloquent\Collection $global_attributes_values
 *
 * @package abc\models
 */
class GlobalAttribute extends BaseModel
{
    protected $primaryKey = 'attribute_id';
    public $timestamps = false;

    protected $casts = [
        'attribute_parent_id' => 'int',
        'attribute_group_id'  => 'int',
        'attribute_type_id'   => 'int',
        'sort_order'          => 'int',
        'required'            => 'int',
        'status'              => 'int',
    ];

    protected $fillable = [
        'attribute_parent_id',
        'attribute_group_id',
        'attribute_type_id',
        'element_type',
        'sort_order',
        'required',
        'settings',
        'status',
        'regexp_pattern',
    ];

    public function global_attributes_descriptions()
    {
        return $this->hasMany(GlobalAttributesDescription::class, 'attribute_id');
    }

    public function global_attributes_value_descriptions()
    {
        return $this->hasMany(GlobalAttributesValueDescription::class, 'attribute_id');
    }

    public function global_attributes_values()
    {
        return $this->hasMany(GlobalAttributesValue::class, 'attribute_id');
    }
}
