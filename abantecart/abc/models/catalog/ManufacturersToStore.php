<?php

namespace abc\models\catalog;

use abc\models\BaseModel;
use abc\models\system\Store;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ManufacturersToStore
 *
 * @property int $manufacturer_id
 * @property int $store_id
 *
 * @property Manufacturer $manufacturer
 * @property Store $store
 *
 * @package abc\models
 */
class ManufacturersToStore extends BaseModel
{
    use SoftDeletes;
    const DELETED_AT = 'date_deleted';

    protected $primaryKey = 'id';
    protected $primaryKeySet = [
        'manufacturer_id',
        'store_id'
    ];

    public $timestamps = false;

    protected $casts = [
        'manufacturer_id' => 'int',
        'store_id'        => 'int',
    ];

    public function manufacturer()
    {
        return $this->belongsTo(Manufacturer::class, 'manufacturer_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
}
