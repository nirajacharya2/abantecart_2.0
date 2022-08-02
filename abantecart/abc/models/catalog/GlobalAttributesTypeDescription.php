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
use abc\models\locale\Language;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class GlobalAttributesTypeDescription
 *
 * @property int $attribute_type_id
 * @property int $language_id
 * @property string $type_name
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @package abc\models
 */
class GlobalAttributesTypeDescription extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'attribute_type_id',
        'language_id',
    ];
    public $timestamps = false;

    protected $casts = [
        'attribute_type_id' => 'int',
        'language_id'       => 'int',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'type_name',
        'date_added',
        'date_modified',
    ];

    public function type()
    {
        return $this->belongsTo(GlobalAttributesType::class, 'attribute_type_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
