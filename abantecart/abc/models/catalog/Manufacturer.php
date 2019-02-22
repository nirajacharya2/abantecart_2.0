<?php

namespace abc\models\catalog;

use abc\models\BaseModel;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Manufacturer
 *
 * @property int $manufacturer_id
 * @property string $name
 * @property int $sort_order
 *
 * @property \Illuminate\Database\Eloquent\Collection $manufacturers_to_stores
 *
 * @package abc\models
 */
class Manufacturer extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;
    const DELETED_AT = 'date_deleted';
    protected $cascadeDeletes = ['stores'];

    protected $primaryKey = 'manufacturer_id';
    public $timestamps = false;

    protected $casts = [
        'sort_order' => 'int',
    ];

    protected $fillable = [
        'name',
        'sort_order',
    ];

    public function stores()
    {
        return $this->hasMany(ManufacturersToStore::class, 'manufacturer_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'manufacturer_id');
    }
}
