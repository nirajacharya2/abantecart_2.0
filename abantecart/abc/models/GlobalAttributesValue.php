<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class GlobalAttributesValue
 *
 * @property int                         $attribute_value_id
 * @property int                         $attribute_id
 * @property int                         $sort_order
 *
 * @property \abc\models\GlobalAttribute $global_attribute
 *
 * @package abc\models
 */
class GlobalAttributesValue extends AModelBase
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
