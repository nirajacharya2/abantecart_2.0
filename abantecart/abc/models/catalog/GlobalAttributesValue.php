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
 * Class GlobalAttributesValue
 *
 * @property int $attribute_value_id
 * @property int $attribute_id
 * @property int $sort_order
 *
 * @property GlobalAttributesValueDescription $description
 * @property GlobalAttributesValueDescription $descriptions
 * @property GlobalAttribute $global_attribute
 *
 * @package abc\models
 */
class GlobalAttributesValue extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['descriptions'];
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

    public function attribute()
    {
        return $this->belongsTo(GlobalAttribute::class, 'attribute_value_id');
    }

    public function description()
    {
        return $this->hasMany(GlobalAttributesValueDescription::class, 'attribute_value_id')
            ->where('language_id', static::$current_language_id)->first();

    }

    public function descriptions()
    {
        return $this->hasMany(GlobalAttributesValueDescription::class, 'attribute_value_id');
    }
}
