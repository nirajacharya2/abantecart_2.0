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
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class TaxClass
 *
 * @property int $tax_class_id
 * @property Carbon $date_added
 * @property Carbon $date_modified
 * @property TaxClassDescription $description
 * @property TaxClassDescription|Collection $descriptions
 * @property TaxRate|Collection $rates
 *
 *
 * @package abc\models
 */
class TaxClass extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['descriptions', 'rates'];

    protected $primaryKey = 'tax_class_id';
    public $timestamps = false;

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'date_added',
        'date_modified',
    ];

    public function description()
    {
        return $this->hasOne(TaxClassDescription::class, 'tax_class_id')
            ->where('language_id', static::$current_language_id);
    }

    public function descriptions()
    {
        return $this->hasMany(TaxClassDescription::class, 'tax_class_id');
    }

    public function rates()
    {
        return $this->hasMany(TaxRate::class, 'tax_class_id');
    }
}
