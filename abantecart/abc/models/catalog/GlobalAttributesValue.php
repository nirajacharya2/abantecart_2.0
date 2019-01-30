<?php

namespace abc\models\catalog;

use abc\models\BaseModel;

/**
 * Class GlobalAttributesValue
 *
 * @property int $attribute_value_id
 * @property int $attribute_id
 * @property int $sort_order
 *
 * @property GlobalAttribute $global_attribute
 *
 * @package abc\models
 */
class GlobalAttributesValue extends BaseModel
{
    protected $primaryKey = 'attribute_value_id';
    public $timestamps = false;

    protected $casts = [
        'attribute_id' => 'int',
        'sort_order'   => 'int',
    ];

    protected $fillable = [
        'attribute_id',
        'sort_order',
    ];

    public function global_attribute()
    {
        return $this->belongsTo(GlobalAttribute::class, 'attribute_id');
    }
}
