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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;

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
 * @property Collection $global_attributes_descriptions
 * @property Collection $global_attributes_value_descriptions
 * @property Collection $global_attributes_values
 *
 * @package abc\models
 */
class GlobalAttribute extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['descriptions', 'value_descriptions', 'values'];

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

    public function description()
    {
        return $this->hasOne(GlobalAttributesDescription::class, 'attribute_id')
                    ->where('language_id', static::$current_language_id);
    }

    public function value_description()
    {
        return $this->hasOne(GlobalAttributesValueDescription::class, 'attribute_id')
                    ->where('language_id', static::$current_language_id);
    }

    public function global_attributes_value_description()
    {
        return $this->hasMany(GlobalAttributesValueDescription::class, 'attribute_id')
                    ->where('language_id', static::$current_language_id);

    }

    public function descriptions()
    {
        return $this->hasMany(GlobalAttributesDescription::class, 'attribute_id');
    }

    public function value_descriptions()
    {
        return $this->hasMany(GlobalAttributesValueDescription::class, 'attribute_id');
    }

    public function values()
    {
        return $this->hasMany(GlobalAttributesValue::class, 'attribute_id');
    }

    public function attribute_group()
    {
        return $this->belongsTo(GlobalAttributesGroup::class, 'attribute_group_id');
    }
}
