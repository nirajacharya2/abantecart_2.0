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
use abc\models\system\TaxRate;
use Carbon\Carbon;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Location
 *
 * @property int $location_id
 * @property string $name
 * @property string $description
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @property Collection $tax_rates
 * @property Collection $zones_to_locations
 *
 * @package abc\models
 */
class Location extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['tax_rates', 'zones_to_locations'];

    protected $primaryKey = 'location_id';
    public $timestamps = false;

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'name',
        'description',
        'date_added',
        'date_modified',
        'location_id'
    ];
    protected $rules = [
        'location_id' => [
            'checks' => [
                'integer',
                'required',
                'sometimes',
                'min:1'
            ],
            'messages' => [
                'integer' => [
                    'language_key' => 'error_location_id',
                    'language_block' => 'localisation/location',
                    'default_text' => 'location id must be integer!',
                    'section' => 'admin'
                ],
                'required' => [
                    'language_key' => 'error_location_id',
                    'language_block' => 'localisation/location',
                    'default_text' => 'location id required!',
                    'section' => 'admin'
                ],
                'min' => [
                    'language_key' => 'error_location_id',
                    'language_block' => 'localisation/location',
                    'default_text' => 'location id must be more 1!',
                    'section' => 'admin'
                ],
            ]
        ],
        'name' => [
            'checks' => [
                'string',
                'min:2',
                'max:32',
                'required',
                'sometimes'
            ],
            'messages' => [
                'min' => [
                    'language_key' => 'error_name',
                    'language_block' => 'localisation/location',
                    'default_text' => 'Name must be more 2 characters',
                    'section' => 'admin'
                ],
                'max' => [
                    'language_key' => 'error_name',
                    'language_block' => 'localisation/location',
                    'default_text' => 'Name must be no more than 32 characters',
                    'section' => 'admin'
                ],
                'required' => [
                    'language_key' => 'error_name',
                    'language_block' => 'localisation/location',
                    'default_text' => 'name required!',
                    'section' => 'admin'
                ],
                'string' => [
                    'language_key' => 'error_name',
                    'language_block' => 'localisation/language',
                    'default_text' => 'name must be string!',
                    'section' => 'admin'
                ],
            ]
        ],
        'description' => [
            'checks' => [
                'string',
                'min:2',
                'max:255',
                'required',
                'sometimes'
            ],
            'messages' => [
                'min' => [
                    'language_key' => 'error_description',
                    'language_block' => 'localisation/location',
                    'default_text' => 'Description must be more 2 characters',
                    'section' => 'admin'
                ],
                'max' => [
                    'language_key' => 'error_description',
                    'language_block' => 'localisation/location',
                    'default_text' => 'Description must be no more than 255 characters',
                    'section' => 'admin'
                ],
                'required' => [
                    'language_key' => 'error_description',
                    'language_block' => 'localisation/location',
                    'default_text' => 'Description required!',
                    'section' => 'admin'
                ],
                'string' => [
                    'language_key' => 'error_description',
                    'language_block' => 'localisation/language',
                    'default_text' => 'Description must be string!',
                    'section' => 'admin'
                ],
            ]
        ]
    ];

    public function tax_rates()
    {
        return $this->hasMany(TaxRate::class, 'location_id');
    }

    public function zones_to_locations()
    {
        return $this->hasMany(ZonesToLocation::class, 'location_id');
    }
}
