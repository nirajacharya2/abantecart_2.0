<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2022 Belavier Commerce LLC
 *
 * This source file is subject to Open Software License (OSL 3.0)
 * License details is bundled with this package in the file LICENSE.txt.
 * It is also available at this URL:
 * <http://www.opensource.org/licenses/OSL-3.0>
 *
 * UPGRADE NOTE:
 * Do not edit or add to this file if you wish to upgrade AbanteCart to newer
 * versions in the future. If you wish to customize AbanteCart for your
 * needs please refer to http://www.abantecart.com for more information.
 */

namespace abc\models\catalog;

use abc\models\BaseModel;
use Dyrynda\Database\Support\CascadeSoftDeletes;
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
        return $this->belongsToMany(
            GlobalAttributesGroup::class,
            'global_attribute_group_to_object_type',
            'attribute_group_id',
            'object_type_id'
        );
    }

    public function global_attributes()
    {
        return $this->hasMany(GlobalAttribute::class, 'attribute_group_id');
    }

    public function description()
    {
        return $this->hasOne(GlobalAttributesGroupsDescription::class, 'attribute_group_id')
            ->where('language_id', static::$current_language_id);
    }

    public function descriptions()
    {
        return $this->hasMany(GlobalAttributesGroupsDescription::class, 'attribute_group_id');
    }

}
