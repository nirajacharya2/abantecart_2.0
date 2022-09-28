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
use abc\models\locale\Location;
use abc\models\locale\Zone;
use Carbon\Carbon;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;

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
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @property TaxClass $tax_class
 * @property Location $location
 * @property Zone $zone
 * @property Collection $tax_rate_descriptions
 *
 * @package abc\models
 */
class TaxRate extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['descriptions'];

    protected $primaryKey = 'tax_rate_id';
    public $timestamps = false;

    protected $casts = [
        'location_id'   => 'int',
        'zone_id'       => 'int',
        'tax_class_id'  => 'int',
        'priority'      => 'int',
        'rate'          => 'float',
        'threshold'     => 'float',
        'date_added'    => 'datetime',
        'date_modified' => 'datetime'
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

    public function descriptions()
    {
        return $this->hasMany(TaxRateDescription::class, 'tax_rate_id');
    }
}
