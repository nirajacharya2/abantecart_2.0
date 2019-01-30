<?php

namespace abc\models\catalog;

use abc\models\BaseModel;

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

    public function product_types()
    {
        return $this->belongsToMany(GlobalAttributesGroup::class, 'global_attribute_group_to_product_type',
            'attribute_group_id', 'product_type_id');
    }

    public function global_attributes()
    {
        return $this->hasMany(GlobalAttribute::class, 'attribute_group_id');
    }
}
