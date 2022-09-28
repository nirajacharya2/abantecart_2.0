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

namespace abc\models\customer;

use abc\core\engine\Registry;
use abc\core\lib\AException;
use abc\models\BaseModel;
use abc\models\locale\Country;
use abc\models\locale\Zone;
use abc\models\QueryBuilder;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;

/**
 * Class Address
 *
 * @property int $address_id
 * @property int $customer_id
 * @property string $company
 * @property string $firstname
 * @property string $lastname
 * @property string $address_1
 * @property string $address_2
 * @property string $postcode
 * @property string $city
 * @property int $country_id
 * @property int $zone_id
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @property Customer $customer
 * @property Country $country
 * @property Zone $zone
 *
 * @method static Address find(int $address_id) Address
 * @method static Address select(mixed $address_id) Builder
 *
 * @package abc\models
 */
class Address extends BaseModel
{
    use SoftDeletes;

    protected $mainClassName = Customer::class;
    protected $mainClassKey = 'customer_id';
    protected $primaryKey = 'address_id';

    protected $casts = [
        'customer_id'   => 'int',
        'country_id'    => 'int',
        'zone_id'       => 'int',
        'date_added'    => 'datetime',
        'date_modified' => 'datetime'
    ];

    protected $guarded = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'address_id',
        'customer_id',
        'company',
        'firstname',
        'lastname',
        'address_1',
        'address_2',
        'postcode',
        'city',
        'country_id',
        'zone_id',
    ];

    protected $touches = ['customer'];

    protected $rules = [
        'address_id'  => [
            'checks'   => [
                'integer',
            ],
            'messages' => [
                'integer' => [
                    'language_key'   => 'error_integer',
                    'language_block' => 'account/address',
                    'default_text'   => 'Address ID must be an integer!',
                    'section'        => 'storefront',
                ],
            ],
        ],
        'customer_id' => [
            'checks'   => [
                'integer',
                'required_with:address_id',
            ],
            'messages' => [
                'integer'                  => [
                    'language_key'   => 'error_integer',
                    'language_block' => 'account/address',
                    'default_text'   => 'Customer ID must be an integer!',
                    'section'        => 'storefront',
                ],
                'required_with:address_id' => [
                    'language_key'   => 'error_required_with_address_id',
                    'language_block' => 'account/address',
                    'default_text'   => 'Customer ID required.',
                    'section'        => 'storefront',
                ],
            ],
        ],
        'company'     => [
            'checks'   => [
                'string',
                'max:32',
            ],
            'messages' => [
                '*' => [
                    'language_key'   => 'error_company',
                    'language_block' => 'account/address',
                    'default_text'   => 'Company Name must be less than 32 character!',
                    'section'        => 'storefront',
                ],
            ],
        ],

        'firstname' => [
            'checks'   => [
                'string',
                //required only when new customer creating
                'required_without:customer_id',
                'between:1,32',
            ],
            'messages' => [
                '*' => [
                    'language_key'   => 'error_firstname',
                    'language_block' => 'account/address',
                    'default_text'   => 'First Name must be between 1 and 32 characters!',
                    'section'        => 'storefront',
                ],
            ],
        ],

        'lastname' => [
            'checks'   => [
                'string',
                //required only when new customer creating
                'required_without:customer_id',
                'between:1,32',
            ],
            'messages' => [
                '*' => [
                    'language_key'   => 'error_lastname',
                    'language_block' => 'account/address',
                    'default_text'   => 'Last Name must be between 1 and 32 characters!',
                    'section'        => 'storefront',
                ],
            ],
        ],

        'address_1' => [
            'checks'   => [
                'string',
                //required only when new customer creating
                'required_without:customer_id',
                'between:3,128',
            ],
            'messages' => [
                '*' => [
                    'language_key'   => 'error_address_1',
                    'language_block' => 'account/address',
                    'default_text'   => 'Address must be between 3 and 128 characters!',
                    'section'        => 'storefront',
                ],
            ],
        ],

        'address_2' => [
            'checks'   => [
                'string',
                'max:128',
            ],
            'messages' => [
                '*' => [
                    'language_key'   => 'error_address_2',
                    'language_block' => 'account/address',
                    'default_text'   => 'Address must be less than 128 character!',
                    'section'        => 'storefront',
                ],
            ],
        ],

        'postcode' => [
            'checks'   => [
                'string',
                //required only when new customer creating
                'required_without:customer_id',
                'between:2,10',
            ],
            'messages' => [
                '*' => [
                    'language_key'   => 'error_postcode',
                    'language_block' => 'account/address',
                    'default_text'   => 'Zip/postal code must be between 2 and 10 characters!',
                    'section'        => 'storefront',
                ],
            ],
        ],

        'city' => [
            'checks'   => [
                'string',
                //required only when new customer creating
                'required_without:customer_id',
                'between:3,128',
            ],
            'messages' => [
                '*' => [
                    'language_key'   => 'error_city',
                    'language_block' => 'account/address',
                    'default_text'   => 'City must be between 3 and 128 characters!',
                    'section'        => 'storefront',
                ],
            ],
        ],

        'country_id' => [
            'checks'   => [
                'integer',
                'required_without:customer_id',
            ],
            'messages' => [
                '*' => [
                    'language_key'   => 'error_country',
                    'language_block' => 'account/address',
                    'default_text'   => 'Please select a country!',
                    'section'        => 'storefront',
                ],
            ],
        ],
        'zone_id'    => [
            'checks'   => [
                'integer',
                'nullable',
                'required_without:customer_id',
            ],
            'messages' => [
                '*' => [
                    'language_key'   => 'error_zone',
                    'language_block' => 'account/address',
                    'default_text'   => 'Please select a region / state!',
                    'section'        => 'storefront',
                ],
            ],
        ],
    ];

    //temporary disable softDeleting
    public function __construct(array $attributes = [])
    {
        $this->forceDeleting = true;
        parent::__construct($attributes);
    }

    /**
     * @param $value
     */
    public function SetZoneIdAttribute($value)
    {
        $value = (int)$value > 0 ? (int)$value : null;
        $this->attributes['zone_id'] = $value;
    }

    /**
     * @param array $options
     *
     * @return bool
     * @throws AException
     */
    public function save(array $options = [])
    {

        $data = $this->attributes;
        $dcrypt = Registry::dcrypt();
        if ($dcrypt->active) {
            $data = $dcrypt->encrypt_data($data, 'addresses');
        }

        $this->attributes = $data;
        return parent::save($options);

    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }

    /**
     * @param int $customer_id
     *
     * @return array
     * @throws AException
     * @throws Exception
     */
    public static function getAddressesByCustomerId(int $customer_id)
    {
        if (!$customer_id) {
            return [];
        }

        $output = [];
        $rows = static::where('customer_id', '=', $customer_id)->get()->toArray();
        if (!$rows) {
            return [];
        }

        $dcrypt = Registry::dcrypt();

        foreach ($rows as $row) {
            $row = $dcrypt->decrypt_data($row, 'addresses');
            $country = null;
            /** @var Country $country */
            if ($row['country_id']) {
                $country = Country::with('description')->find($row['country_id']);
            }
            if ($country) {
                $country_name = $country->description->name;
                $iso_code_2 = $country->iso_code_2;
                $iso_code_3 = $country->iso_code_3;
                $address_format = $country->address_format;
            } else {
                $country_name = '';
                $iso_code_2 = '';
                $iso_code_3 = '';
                $address_format = '';
            }

            $zone = null;
            /** @var Zone $zone */
            if ($row['zone_id']) {
                $zone = Zone::with('description')->find($row['zone_id']);
            }
            if ($zone) {
                $zone_name = $zone->description->name;
                $zone_code = $zone->code;
            } else {
                $zone_name = '';
                $zone_code = '';
            }

            $output[$row['address_id']] = [
                'address_id'     => $row['address_id'],
                'firstname'      => $row['firstname'],
                'lastname'       => $row['lastname'],
                'company'        => $row['company'],
                'address_1'      => $row['address_1'],
                'address_2'      => $row['address_2'],
                'postcode'       => $row['postcode'],
                'city'           => $row['city'],
                'zone_id'        => (int)$row['zone_id'],
                'zone'           => $zone_name,
                'zone_code'      => $zone_code,
                'country_id'     => (int)$row['country_id'],
                'country'        => $country_name,
                'iso_code_2'     => $iso_code_2,
                'iso_code_3'     => $iso_code_3,
                'address_format' => $address_format,
            ];
        }
        return $output;
    }

    /**
     * @param int $customer_id
     * @param int $language_id
     * @param int|null $address_id
     *
     * @return Collection|Model
     */
    public static function getAddresses(int $customer_id, int $language_id, int $address_id = null)
    {
        /**
         * @var QueryBuilder $query
         */
        $query = Address::select(
            [
                'addresses.*',
                'countries.*',
                'zones.*',
                'country_descriptions.name as country',
                'zone_descriptions.name as zone'
            ]
        )->where('customer_id', '=', $customer_id);
        if ($address_id) {
            $query->where('address_id', '=', $address_id);
        }
        $query->leftJoin(
            'countries',
            function ($join) {
                /** @var JoinClause $join */
                $join->on('addresses.country_id', '=', 'countries.country_id');
            }
        );
        $query->leftJoin(
            'country_descriptions',
            function ($join) use ($language_id) {
                /** @var JoinClause $join */
                $join->on('country_descriptions.country_id', '=', 'countries.country_id')
                    ->where('country_descriptions.language_id', '=', $language_id);
            }
        );
        $query->leftJoin(
            'zones',
            function ($join) {
                /** @var JoinClause $join */
                $join->on('addresses.zone_id', '=', 'zones.zone_id');
            }
        );
        $query->leftJoin(
            'zone_descriptions',
            function ($join) use ($language_id) {
                /** @var JoinClause $join */
                $join->on('zone_descriptions.zone_id', '=', 'zones.zone_id')
                    ->where('zone_descriptions.language_id', '=', $language_id);
            }
        );

        //allow to extend this method from extensions
        Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $query, func_get_args());
        return $address_id
            ? $query->first()
            : $query->useCache('customer')->get();
    }
}