<?php

namespace abc\models\base;

use abc\models\AModelBase;

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
class ManufacturersToStore extends AModelBase
{
    public $incrementing = false;
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
