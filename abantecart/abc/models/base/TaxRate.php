<?php

namespace abc\models\base;

use abc\models\AModelBase;

/**
 * Class TaxRate
 *
 * @property int $tax_rate_id
 * @property int $location_id
 * @property int $zone_id
 * @property int $tax_class_id
 * @property int $priority
 * @property float $rate
 * @property string $rate_prefix
 * @property string $threshold_condition
 * @property float $threshold
 * @property string $tax_exempt_groups
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 *
 * @property TaxClass $tax_class
 * @property Location $location
 * @property Zone $zone
 * @property \Illuminate\Database\Eloquent\Collection $tax_rate_descriptions
 *
 * @package abc\models
 */
class TaxRate extends AModelBase
{
    protected $primaryKey = 'tax_rate_id';
    public $timestamps = false;

    protected $casts = [
        'location_id'  => 'int',
        'zone_id'      => 'int',
        'tax_class_id' => 'int',
        'priority'     => 'int',
        'rate'         => 'float',
        'threshold'    => 'float',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'location_id',
        'zone_id',
        'tax_class_id',
        'priority',
        'rate',
        'rate_prefix',
        'threshold_condition',
        'threshold',
        'tax_exempt_groups',
        'date_added',
        'date_modified',
    ];

    public function tax_class()
    {
        return $this->belongsTo(TaxClass::class, 'tax_class_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }

    public function tax_rate_descriptions()
    {
        return $this->hasMany(TaxRateDescription::class, 'tax_rate_id');
    }
}
