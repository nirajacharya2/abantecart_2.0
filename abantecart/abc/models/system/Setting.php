<?php

namespace abc\models\system;

use abc\models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Setting
 *
 * @property int $setting_id
 * @property int $store_id
 * @property string $group
 * @property string $key
 * @property string $value
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
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
     * @return object
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
