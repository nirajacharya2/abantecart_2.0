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
namespace abc\models\system;

use abc\models\BaseModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use stdClass;

/**
 * Class Setting
 *
 * @property int $setting_id
 * @property int $store_id
 * @property string $group
 * @property string $key
 * @property string $value
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @property Store $store
 *
 * @package abc\models
 */
class Setting extends BaseModel
{
    protected $primaryKey = 'setting_id';

    use SoftDeletes;

    public $timestamps = false;

    protected $casts = [
        'store_id' => 'int',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'store_id',
        'group',
        'group_id',
        'key',
        'value',
        'date_added',
        'date_modified',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    /**
     * @param int $storeId
     *
     * @return stdClass
     */
    public static function getStoreSettings($storeId)
    {
        $items = static::where('store_id', '=', $storeId)->get();
        $output = [];
        foreach ($items as $row){
            $output[$row->key] = $row->value;
        }
        return (object)$output;
    }
}
