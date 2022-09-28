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
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ObjectType
 *
 * @property int product_type_id
 *
 * @package abc\models
 */
class ObjectType extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'object_type_id';
    public $timestamps = false;

    protected $casts = [
        'date_added'    => 'datetime',
        'date_modified' => 'datetime'
    ];

    protected $fillable = [
        'object_type',
        'status',
        'sort_order',
        'stage_id',
    ];

    public function description()
    {
        return $this->hasOne(ObjectTypeDescription::class, 'object_type_id')
            ->where('language_id', static::$current_language_id);
    }

    public function descriptions()
    {
        return $this->hasMany(ObjectTypeDescription::class, 'object_type_id');
    }

    public function global_attribute_groups()
    {
        return $this->belongsToMany(
            GlobalAttributesGroup::class,
            'global_attribute_group_to_object_type',
            'object_type_id',
            'attribute_group_id'
        );
    }

    /**
     * @param array $data
     * @return array
     */
    public function getObjectTypes($data)
    {
        return self::with(['description'])
            ->setGridRequest($data)
            ->get()
            ->toArray();
    }

    /**
     * @param int $object_type_id
     * @return array
     */
    public function getObjectType($object_type_id)
    {
        return ObjectType::with('description', 'global_attribute_groups')
            ->find($object_type_id)
            ->toArray();
    }

}