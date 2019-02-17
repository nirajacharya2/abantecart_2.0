<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2018 Belavier Commerce LLC
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
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class ObjectType
 *
 * @property int product_type_id
 *
 * @package abc\models
 */
class ObjectType extends BaseModel
{
    protected $primaryKey = 'object_type_id';
    public $timestamps = false;

    protected $dates = [
        'date_added',
        'date_modified',
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
            ->where('language_id', $this->registry->get('language')->getContentLanguageID());
    }

    public function descriptions()
    {
        return $this->hasMany(ObjectTypeDescription::class, 'object_type_id');
    }

    public function global_attribute_groups()
    {
        return $this->belongsToMany(GlobalAttributesGroup::class, 'global_attribute_group_to_object_type',
            'object_type_id', 'attribute_group_id');
    }


    public function getObjectTypes($data)
    {
        $results = self::with(['description'])
            ->setGridRequest($data)
            ->get()
            ->toArray();

        return $results;
    }

    public function getObjectType($object_type_id)
    {
        $result = ObjectType::with('description', 'global_attribute_groups')
            ->find($object_type_id)
           ->toArray();
        return $result;
    }

}