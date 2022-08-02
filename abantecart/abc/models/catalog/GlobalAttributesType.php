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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class GlobalAttributesType
 *
 * @property int $attribute_type_id
 * @property string $type_key
 * @property string $controller
 * @property int $sort_order
 * @property int $status
 *
 * @property GlobalAttributesTypeDescription $description
 * @property GlobalAttributesTypeDescription $descriptions
 *
 * @package abc\models
 */
class GlobalAttributesType extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['descriptions'];
    protected $primaryKey = 'attribute_type_id';
    public $timestamps = false;

    protected $casts = [
        'sort_order' => 'int',
        'status'     => 'int',
    ];

    protected $fillable = [
        'type_key',
        'controller',
        'sort_order',
        'status',
    ];

    /**
     * @return HasOne
     */
    public function description()
    {
        return $this->hasOne(GlobalAttributesTypeDescription::class, 'attribute_type_id', 'attribute_type_id')
            ->where('language_id', '=', static::$current_language_id);
    }
    /**
     * @return HasMany
     */
    public function descriptions()
    {
        return $this->hasMany(GlobalAttributesTypeDescription::class, 'attribute_type_id');
    }
}
