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

namespace abc\models\locale;

use abc\models\BaseModel;
use Carbon\Carbon;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class WeightClass
 *
 * @property int $weight_class_id
 * @property float $value
 * @property string $iso_code
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @property WeightClassDescription $description
 * @property WeightClassDescription $descriptions
 *
 * @package abc\models
 */
class WeightClass extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['descriptions'];

    protected $primaryKey = 'weight_class_id';
    public $timestamps = false;

    protected $casts = [
        'value' => 'float',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'weight_class_id',
        'value',
        'date_added',
        'date_modified',
    ];
    protected $rules = [
        'weight_class_id' => [
            'checks'   => [
                'integer',
                'required',
                'sometimes',
                'min:1'
            ],
            'messages' => [
                'integer'  => [
                    'language_key'   => 'error_weight_class_id',
                    'language_block' => 'localisation/weight_class',
                    'default_text'   => 'weight class id must be integer!',
                    'section'        => 'admin'
                ],
                'required' => [
                    'language_key'   => 'error_weight_class_id',
                    'language_block' => 'localisation/weight_class',
                    'default_text'   => 'weight class id required!',
                    'section'        => 'admin'
                ],
                'min'      => [
                    'language_key'   => 'error_weight_class_id',
                    'language_block' => 'localisation/weight_class',
                    'default_text'   => 'weight class id must be more 1!',
                    'section'        => 'admin'
                ],
            ]
        ],
    ];

    public function description()
    {
        return $this->hasOne(WeightClassDescription::class, 'weight_class_id')
            ->where('language_id', static::$current_language_id);
    }

    public function descriptions()
    {
        return $this->hasMany(WeightClassDescription::class, 'weight_class_id');
    }
}
