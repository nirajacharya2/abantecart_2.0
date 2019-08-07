<?php

namespace abc\models\order;

use abc\core\engine\Registry;
use abc\core\lib\ADataEncryption;
use abc\models\BaseModel;
use abc\models\customer\Customer;
use abc\models\locale\Country;
use abc\models\locale\Currency;
use abc\models\locale\Language;
use abc\models\locale\Zone;
use abc\models\QueryBuilder;
use abc\models\system\Store;
use Exception;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\JoinClause;

/**
 * Class Order
 *
 * @property int $order_id
 * @property int $invoice_id
 * @property string $invoice_prefix
 * @property int $store_id
 * @property string $store_name
 * @property string $store_url
 * @property int $customer_id
 * @property int $customer_group_id
 * @property string $firstname
 * @property string $lastname
 * @property string $telephone
 * @property string $fax
 * @property string $email
 * @property string $shipping_firstname
 * @property string $shipping_lastname
 * @property string $shipping_company
 * @property string $shipping_address_1
 * @property string $shipping_address_2
 * @property string $shipping_city
 * @property string $shipping_postcode
 * @property string $shipping_zone
 * @property int $shipping_zone_id
 * @property string $shipping_country
 * @property int $shipping_country_id
 * @property string $shipping_address_format
 * @property string $shipping_method
 * @property string $shipping_method_key
 * @property string $payment_firstname
 * @property string $payment_lastname
 * @property string $payment_company
 * @property string $payment_address_1
 * @property string $payment_address_2
 * @property string $payment_city
 * @property string $payment_postcode
 * @property string $payment_zone
 * @property int $payment_zone_id
 * @property string $payment_country
 * @property int $payment_country_id
 * @property string $payment_address_format
 * @property string $payment_method
 * @property string $payment_method_key
 * @property string $comment
 * @property float $total
 * @property int $order_status_id
 * @property int $language_id
 * @property int $currency_id
 * @property float $value
 * @property int $coupon_id
 * @property \Carbon\Carbon $date_added
 * @property \Carbon\Carbon $date_modified
 * @property string $ip
 * @property string $payment_method_data
 *
 * @property Store $store
 * @property Language $language
 * @property Currency $currency
 * @property Customer $customer
 * @property Coupon $coupon
 * @property OrderStatus $order_status
 * @property \Illuminate\Database\Eloquent\Collection $order_data
 * @property \Illuminate\Database\Eloquent\Collection $order_downloads
 * @property \Illuminate\Database\Eloquent\Collection $order_downloads_histories
 * @property \Illuminate\Database\Eloquent\Collection $order_products
 * @property \Illuminate\Database\Eloquent\Collection $order_totals
 *
 * @method static QueryBuilder where(string | array $column, string $operator = '=', mixed $value = null, string $boolean = 'and') QueryBuilder
 *
 * @package abc\models
 */
class Order extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = [
        'data',
        'order_products',
        'downloads', //see Download model. there cascade deleting too
        'totals',
    ];

    public $timestamps = false;
    protected $primaryKey = 'order_id';

    /**
     * Access policy properties
     * Note: names must be without dashes and whitespaces
     * policy rule will be named as {userType-userGroup}.product-product-read
     * For example: system-www-data.product-product-read
     */
    protected $policyGroup = 'order';
    protected $policyObject = 'order';

    protected $casts = [
        'invoice_id'          => 'int',
        'store_id'            => 'int',
        'customer_id'         => 'int',
        'customer_group_id'   => 'int',
        'shipping_zone_id'    => 'int',
        'shipping_country_id' => 'int',
        'payment_zone_id'     => 'int',
        'payment_country_id'  => 'int',
        'total'               => 'float',
        'order_status_id'     => 'int',
        'language_id'         => 'int',
        'currency_id'         => 'int',
        'value'               => 'float',
        'coupon_id'           => 'int',
        'payment_method_data' => 'serialized',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'order_id',
        'invoice_id',
        'invoice_prefix',
        'store_id',
        'store_name',
        'store_url',
        'customer_id',
        'customer_group_id',
        'firstname',
        'lastname',
        'telephone',
        'fax',
        'email',
        'shipping_firstname',
        'shipping_lastname',
        'shipping_company',
        'shipping_address_1',
        'shipping_address_2',
        'shipping_city',
        'shipping_postcode',
        'shipping_zone',
        'shipping_zone_id',
        'shipping_country',
        'shipping_country_id',
        'shipping_address_format',
        'shipping_method',
        'shipping_method_key',
        'payment_firstname',
        'payment_lastname',
        'payment_company',
        'payment_address_1',
        'payment_address_2',
        'payment_city',
        'payment_postcode',
        'payment_zone',
        'payment_zone_id',
        'payment_country',
        'payment_country_id',
        'payment_address_format',
        'payment_method',
        'payment_method_key',
        'comment',
        'total',
        'order_status_id',
        'language_id',
        'currency_id',
        'currency',
        'value',
        'coupon_id',
        'date_added',
        'date_modified',
        'ip',
        'payment_method_data',
    ];

    protected $rules = [
        /** @see validate() */
        'order_id'       => [
            'checks'   => [
                'integer',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer!',
                ],
            ],
        ],
        'invoice_id'     => [
            'checks'   => [
                'integer',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer!',
                ],
            ],
        ],
        'invoice_prefix' => [
            'checks'   => [
                'string',
                'max:10',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
        'store_id'       => [
            'checks'   => [
                'integer',
                'exists:stores',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer!',
                ],
            ],
        ],
        'store_name'     => [
            'checks'   => [
                'string',
                'max:64',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
        'store_url'      => [
            'checks'   => [
                'url',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be valid URL!',
                ],
            ],
        ],

        'customer_id'       => [
            'checks'   => [
                'integer',
                'nullable',
                'exists:customers',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer Or absent in customers table!',
                ],
            ],
        ],
        'customer_group_id' => [
            'checks'   => [
                'integer',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer!',
                ],
            ],
        ],

        'firstname' => [
            'checks'   => [
                'string',
                'max:32',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
        'lastname'  => [
            'checks'   => [
                'string',
                'max:32',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
        'telephone' => [
            'checks'   => [
                'string',
                'max:32',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
        'fax'       => [
            'checks'   => [
                'string',
                'max:32',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
        'email'     => [
            'checks'   => [
                'string',
                'max:96',
                'regex:/^[A-Z0-9._%-]+@[A-Z0-9.-]{0,61}[A-Z0-9]\.[A-Z]{2,16}$/i',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'Email must be valid!',
                ],
            ],
        ],

        'shipping_firstname'      => [
            'checks'   => [
                'string',
                'max:32',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
        'shipping_lastname'       => [
            'checks'   => [
                'string',
                'max:32',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
        'shipping_company'        => [
            'checks'   => [
                'string',
                'max:32',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
        'shipping_address_1'      => [
            'checks'   => [
                'string',
                'max:128',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
        'shipping_address_2'      => [
            'checks'   => [
                'string',
                'max:128',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
        'shipping_city'           => [
            'checks'   => [
                'string',
                'max:128',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
        'shipping_postcode'       => [
            'checks'   => [
                'string',
                'max:10',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
        'shipping_zone'           => [
            'checks'   => [
                'string',
                'max:128',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
        'shipping_zone_id'        => [
            'checks'   => [
                'int',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer!',
                ],
            ],
        ],
        'shipping_country'        => [
            'checks'   => [
                'string',
                'max:128',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
        'shipping_country_id'     => [
            'checks'   => [
                'int',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer!',
                ],
            ],
        ],
        'shipping_address_format' => [
            'checks'   => [
                'string',
                'max:1500',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
        'shipping_method'         => [
            'checks'   => [
                'string',
                'max:128',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
        'shipping_method_key'     => [
            'checks'   => [
                'string',
                'max:128',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],

        'payment_firstname'      => [
            'checks'   => [
                'string',
                'max:32',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
        'payment_lastname'       => [
            'checks'   => [
                'string',
                'max:32',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
        'payment_company'        => [
            'checks'   => [
                'string',
                'max:32',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
        'payment_address_1'      => [
            'checks'   => [
                'string',
                'max:128',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
        'payment_address_2'      => [
            'checks'   => [
                'string',
                'max:128',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
        'payment_city'           => [
            'checks'   => [
                'string',
                'max:128',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
        'payment_postcode'       => [
            'checks'   => [
                'string',
                'max:10',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
        'payment_zone'           => [
            'checks'   => [
                'string',
                'max:128',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
        'payment_zone_id'        => [
            'checks'   => [
                'int',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer!',
                ],
            ],
        ],
        'payment_country'        => [
            'checks'   => [
                'string',
                'max:128',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
        'payment_country_id'     => [
            'checks'   => [
                'int',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer!',
                ],
            ],
        ],
        'payment_address_format' => [
            'checks'   => [
                'string',
                'max:1500',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
        'payment_method'         => [
            'checks'   => [
                'string',
                'max:128',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
        'payment_method_key'     => [
            'checks'   => [
                'string',
                'max:128',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],

        'comment' => [
            'checks'   => [
                'string',
                'max:1500',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be string :max characters length!',
                ],
            ],
        ],
        'total'   => [
            'checks'   => [
                'numeric',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be numeric!',
                ],
            ],
        ],

        'order_status_id' => [
            'checks'   => [
                'int',
                'exists:order_statuses',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer or absent in order_statuses table!',
                ],
            ],
        ],
        'language_id'     => [
            'checks'   => [
                'int',
                'exists:languages',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer or absent in languages table!',
                ],
            ],
        ],
        'currency_id'     => [
            'checks'   => [
                'int',
                'exists:currencies',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer or absent in currencies table!',
                ],
            ],
        ],
        'currency'        => [
            'checks'   => [
                'string',
                'max:3',
                'exists:currencies,code',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute code must be string :max characters length and must be presents in the table currencies!',
                ],
            ],
        ],

        'value'               => [
            'checks'   => [
                'numeric',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be numeric!',
                ],
            ],
        ],
        'coupon_id'           => [
            'checks'   => [
                'int',
                'nullable',
                'exists:coupons',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer or absent in coupons table!',
                ],
            ],
        ],
        'ip'                  => [
            'checks'   => [
                'ip',
                'max:50',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be ip-address and maximum :max characters length!',
                ],
            ],
        ],
        'payment_method_data' => [
            'checks'   => [
                'array',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be a array!',
                ],
            ],
        ],

    ];

    /**
     * @param array $options
     *
     * @return bool
     * @throws \abc\core\lib\AException
     */
    public function save(array $options = [])
    {

        $data = $this->attributes;
        /**
         * @var ADataEncryption $dcrypt
         */
        $dcrypt = Registry::dcrypt();
        if ($dcrypt->active) {
            $data = $dcrypt->encrypt_data($data, 'orders');
        }

        $this->attributes = $data;
        return parent::save($options);
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }

    public function order_status()
    {
        return $this->belongsTo(OrderStatus::class, 'order_status_id');
    }

    public function data()
    {
        return $this->hasMany(OrderDatum::class, 'order_id');
    }

    public function downloads()
    {
        return $this->hasMany(OrderDownload::class, 'order_id');
    }

    public function downloads_histories()
    {
        return $this->hasMany(OrderDownloadsHistory::class, 'order_id');
    }

    public function order_products()
    {
        return $this->hasMany(OrderProduct::class, 'order_id');
    }

    public function totals()
    {
        return $this->hasMany(OrderTotal::class, 'order_id');
    }

    /**
     * @param int $order_id
     * @param int|null|string $order_status_id - if NUll - seek greater than 0, 'any' - try to get all statuses, integer - seek specific order status
     * @param int|null $customer_id
     *
     * @return array
     * @throws \abc\core\lib\AException
     */
    public static function getOrderArray($order_id, $order_status_id = null, $customer_id = null)
    {
        $customer_id = (int)$customer_id;
        $order = null;
        try {
            /**
             * @var QueryBuilder $query
             */
            $query = Order::select(['orders.*', 'order_status_descriptions.name as order_status_name'])
                          ->where('orders.order_id', '=', $order_id);
            if ($customer_id) {
                $query->where('orders.customer_id', '=', $customer_id);
            }

            $query->leftJoin(
                'languages',
                'languages.language_id',
                '=',
                'orders.language_id'
            );
            $query->leftJoin(
                'order_status_descriptions',
                function ($join) {
                    /**
                     * @var JoinClause $join
                     */
                    $join
                        ->on(
                            'orders.order_status_id',
                            '=',
                            'order_status_descriptions.order_status_id'
                        )->where(
                            'order_status_descriptions.language_id',
                            '=',
                            'orders.language_id'
                        );
                }
            );

            if ($order_status_id === null) {
                //processed order
                $query->where('orders.order_status_id', '>', '0');

            } elseif ($order_status_id == 'any') {
                //unrestricted to status
            } else {
                //only specific status
                $query->where('orders.order_status_id', '=', (int)$order_status_id);
            }

            //allow to extends this method from extensions
            Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $query, func_get_args());
            /**
             * @var Order $order
             */
            $order = $query->first();
        }catch(Exception $e){
            Registry::log()->write(__CLASS__.': '. $e->getMessage());

        }
        $order_data = [];
        if ($order) {
            $order_data = Registry::dcrypt()->decrypt_data($order->toArray(), 'orders');
            $country = Country::find($order_data['shipping_country_id']);
            $order_data['shipping_iso_code_2'] = $country ? $country->iso_code_2 : '';
            $order_data['shipping_iso_code_3'] = $country ? $country->iso_code_3 : '';

            $zone = Zone::find($order_data['shipping_zone_id']);
            $order_data['shipping_zone_code'] = $zone ? $zone->code : '';

            $country = Country::find($order_data['payment_country_id']);
            $order_data['payment_iso_code_2'] = $country ? $country->iso_code_2 : '';
            $order_data['payment_iso_code_3'] = $country ? $country->iso_code_3 : '';

            $zone = Zone::find($order_data['payment_zone_id']);
            $order_data['payment_zone_code'] = $zone ? $zone->code : '';
        }

        return $order_data;
    }

    /**
     * @param $customer_id
     * @param int $start
     * @param int $limit
     * @param int $order_id
     *
     * @return array
     */
    public function getCustomerOrdersArray($customer_id, $start = 0, $limit = 20, $order_id = 0)
    {
        $query = Order::where('customer_id', '=', $customer_id)
                      ->where('order_status_id', '>', 0)
                      ->limit($limit)
                      ->offset($start);
        Registry::extensions()->hk_extendQuery($this, __FUNCTION__, $query, func_get_args());
        return $query->get()->toArray();
    }

    /**
     * @param $order_id
     * @param $order_product_id
     *
     * @return \Illuminate\Support\Collection
     */
    public function getOrderOptions($order_id, $order_product_id)
    {
        /**
         * @var QueryBuilder $query
         */
        $query = OrderOption::where(
                                [
                                    'order_id'         => $order_id,
                                    'order_product_id' => $order_product_id,
                                ]
                            )
                            ->select(['order_options.*', 'product_options.element_type'])
                            ->leftJoin(
                                'product_option_values',
                                'product_option_values.product_option_value_id',
                                '=',
                                'order_options.product_option_value_id'
                            )
                            ->leftJoin(
                                'product_options',
                                'product_options.product_option_id',
                                '=',
                                'product_option_values.product_option_id'
                            );
        Registry::extensions()->hk_extendQuery($this, __FUNCTION__, $query, func_get_args());
        return $query->get();
    }

    /**
     * @param int $order_id
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getOrderHistories($order_id)
    {
        $query = OrderHistory::select(
                        [
                            'order_history.*',
                            'order_status_descriptions.name AS order_status_name',
                        ]
        )->where(
            [
                'order_history.order_id' => $order_id,
                'order_history.notify' => 1
            ]
        )->leftJoin(
            'orders',
            'orders.order_id',
            '=',
            'order_history.order_id'
        )->leftJoin(
            'order_status_descriptions',
            function($join){
                /**
                 * @var JoinClause $join
                 */
                $join
                ->on(
                    'orders.order_status_id',
                    '=',
                    'order_status_descriptions.order_status_id'
                )->where(
                    'order_status_descriptions.language_id',
                    '=',
                    'orders.language_id'
                );
            }
        )->orderBy(
            'order_history.date_added'
        );
        return $query->get();
    }
}
