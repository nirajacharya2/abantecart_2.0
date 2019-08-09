<?php

namespace abc\models\order;

use abc\core\engine\ALanguage;
use abc\core\engine\HtmlElementFactory;
use abc\core\engine\Registry;
use abc\core\lib\ADataEncryption;
use abc\models\BaseModel;
use abc\models\catalog\Product;
use abc\models\catalog\ProductOption;
use abc\models\catalog\ProductOptionValue;
use abc\models\customer\Customer;
use abc\models\locale\Country;
use abc\models\locale\Currency;
use abc\models\locale\Language;
use abc\models\locale\Zone;
use abc\models\QueryBuilder;
use abc\models\system\Store;
use Exception;
use H;
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
 * @method static Order find(int $order_id) Order
 * @method static Order select(mixed $select) Builder
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

        'value' => [
            'checks'   => [
                'numeric',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be numeric!',
                ],
            ],
        ],
        'coupon_id'      => [
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
        'ip'             => [
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
    ];

    public function setPaymentMethodDataAttribute($value)
    {
        $this->attributes['payment_method_data'] = serialize($value);
    }

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

    public function delete()
    {

        if (Registry::config()->get('config_stock_subtract') && $this->attributes['order_status_id'] > 0) {

            $orderProducts = OrderProduct::where('order_id', '=', $order_id)->get();
            if ($orderProducts) {


                foreach ($orderProducts as $orderProduct) {
                    $product = Product::find($orderProduct->product_id);
                    $product->update(
                        [
                            'quantity' => $product->quantity + $orderProduct['quantity'],
                        ]
                    );

                    $orderOptions = OrderOption::where(
                        [
                            'order_id'         => $order_id,
                            'order_product_id' => $orderProduct['order_product_id'],
                        ]
                    )->get();
                    if ($orderOptions) {
                        foreach ($orderOptions as $orderOption) {
                            $option = ProductOptionValue::where(
                                [
                                    'product_option_value_id' => $orderOption['product_option_value_id'],
                                    'subtract'                => 1,
                                ]
                            )->get();
                            if ($option) {
                                $option->update(
                                    [
                                        'quantity' => $option->quantity + $orderProduct['quantity'],
                                    ]
                                );
                            }
                        }
                    }
                }
            }
        }
        parent::delete();
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
            $order_data['im'] = static::getImFromOrderData((int)$order_id, (int)$order_data['customer_id']);
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
                'order_history.notify'   => 1
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

    /**
     * @param int $order_id
     * @param int $customer_id
     *
     * @return array
     * @throws \Exception
     */
    public static function getImFromOrderData(int $order_id, $customer_id)
    {
        $order_id = (int)$order_id;
        if (!$order_id) {
            return [];
        }
        $im = Registry::im();
        $protocols = $im->getProtocols();
        if (!$protocols) {
            return [];
        }

        $dataTypes = OrderDataType::whereIn('name', $protocols)
                                  ->get()
                                  ->pluck('type_id');
        /**
         * @var QueryBuilder $query
         */
        $query = OrderDatum::where(
            [
                'order_id' => $order_id,
            ]);
        $query->whereIn('order_data.type_id', $dataTypes)
              ->leftJoin('order_data_types',
                  'order_data.type_id',
                  '=',
                  'order_data_types.type_id'
              );
        $query->whereNotIn('order_data_types.name', ['email']);

        //allow to extends this method from extensions
        Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $query, func_get_args());
        /**
         * @var Order $order
         */
        $output = $query->get()->pluck('data', 'name')->toArray();

        if ($customer_id) {
            foreach ($protocols as $protocol) {
                if ($output[$protocol]) {
                    continue;
                }
                $uri = $im->getCustomerURI($protocol, $customer_id, $order_id);
                $output[$protocol] = ['uri' => $uri];
            }
        }

        return $output;
    }

    public static function editOrder(int $order_id, array $data)
    {
        $order = Order::find($order_id);
        if (!$order) {
            return false;
        }

        $order->fill($data);
        $order->save();

        $orderInfo = Order::getOrderArray($order_id);
        $language_code = Language::getCodeById($orderInfo['language_id']);
        $oLanguage = new ALanguage(Registry::getInstance(), $language_code);

        $old_language = Product::getCurrentLanguageID();

        if (isset($data['product'])) {
            // first of all delete removed products
            $order_product_ids = [];
            foreach ($data['product'] as $item) {
                if ($item['order_product_id']) {
                    $order_product_ids[] = $item['order_product_id'];
                }
            }
            /**
             * @var QueryBuilder $query
             */
            $query = OrderProduct::where('order_id', '=', $order_id)
                                 ->whereNotIn('order_product_id', $order_product_ids);
            //remove deleted products from order
            $query->forceDelete();

            /*$this->db->query("DELETE FROM ".$this->db->table_name("order_products")."
                              WHERE order_id = '".(int)$order_id."' 
                                AND order_product_id NOT IN ('".(implode("','", $order_product_ids))."')");*/

            foreach ($data['product'] as $product) {
                if ($product['product_id']) {
                    $exists = OrderProduct::where(
                        [
                            'order_id'         => $order_id,
                            'product_id'       => (int)$product['product_id'],
                            'order_product_id' => (int)$product['order_product_id'],
                        ]
                    )->get();

                    /*$exists = $this->db->query(
                        "SELECT product_id
                         FROM ".$this->db->table_name("order_products")."
                         WHERE order_id = '".(int)$order_id."'
                            AND product_id='".(int)$product['product_id']."'
                            AND order_product_id = '".(int)$product['order_product_id']."'"
                    );
                    $exists = $exists->num_rows;*/
                    if ($exists) {
                        $exists->update(
                            [
                                'price'    => \H::preformatFloat($product['price'], $oLanguage->get('decimal_point'))
                                    / $orderInfo['value'],
                                'total'    => \H::preformatFloat($product['total'], $oLanguage->get('decimal_point'))
                                    / $orderInfo['value'],
                                'quantity' => $product['quantity'],
                            ]
                        );
                        /* $this->db->query(
                             "UPDATE ".$this->db->table_name("order_products")."
                             SET price = '".
                             $this->db->escape(
                                 (H::preformatFloat($product['price'],Registry::language()->get('decimal_point'))
                                     /
                                     $order['value'])
                             )."',
                                   total = '".
                             $this->db->escape(
                                 (H::preformatFloat($product['total'],Registry::language()->get('decimal_point'))
                                     /
                                     $order['value'])
                             )."',
                             quantity = '".$this->db->escape($product['quantity'])."'
                             WHERE order_id = '".(int)$order_id."'
                                 AND order_product_id = '".(int)$product['order_product_id']."'"
                         );*/
                    } else {
                        // new products
                        //set order language as current language of model
                        Product::setCurrentLanguageID($orderInfo['language_id']);
                        /**
                         * @var Product $newProduct
                         */
                        $newProduct = Product::with('description')->find($product['product_id']);

                        $orderProduct = new OrderProduct(
                            [
                                'order_id'   => $order_id,
                                'product_id' => $product['product_id'],
                                'name'       => $newProduct->description->name,
                                'model'      => $newProduct->model,
                                'sku'        => $newProduct->sku,
                                'price'      => \H::preformatFloat($product['price'], $oLanguage->get('decimal_point'))
                                    / $orderInfo['value'],
                                'total'      => \H::preformatFloat($product['total'], $oLanguage->get('decimal_point'))
                                    / $orderInfo['value'],
                                'quantity'   => $product['quantity'],
                            ]
                        );

                        $orderProduct->save();

                        /* $product_query = $this->db->query(
                             "SELECT *, p.product_id
                              FROM ".$this->db->table_name("products")." p
                              LEFT JOIN ".$this->db->table_name("product_descriptions")." pd
                                 ON (p.product_id = pd.product_id)
                              WHERE p.product_id='".(int)$product['product_id']."'"
                         );

                         $this->db->query(
                             "INSERT INTO ".$this->db->table_name("order_products")."
                             SET order_id = '".(int)$order_id."',
                                 product_id = '".(int)$product['product_id']."',
                                 NAME = '".$this->db->escape($product_query->row['name'])."',
                                 model = '".$this->db->escape($product_query->row['model'])."',
                                 sku = '".$this->db->escape($product_query->row['sku'])."',
                                 price = '".$this->db->escape(
                                     (H::preformatFloat($product['price'],$this->language->get('decimal_point'))
                                         / $order['value'])
                                 )."',
                                 total = '".$this->db->escape(
                                     (H::preformatFloat($product['total'],$this->language->get('decimal_point'))
                                         / $order['value'])
                                 )."',
                                 quantity = '".$this->db->escape($product['quantity'])."'"
                         );*/
                    }

                    static::editOrderProduct($orderInfo, $product, $oLanguage);
                }
            }
        }

        if ($data['order_totals']) {
            //remove previous totals
            OrderTotal::where('order_id', '=', $order_id)->forceDelete();

            foreach ($data['order_totals'] as $orderTotal) {
                $total = new OrderTotal($orderTotal);
                $total->save();
            }
        }
//        H::event('abc\models\admin\order@update', [new ABaseEvent($order_id, $data)]);
    }

    /**
     * @param array $orderInfo
     * @param array $data
     *
     * @param null|ALanguage $language
     *
     * @return bool
     * @throws Exception
     */

    protected static function editOrderProduct(array $orderInfo, array $data, $language = null)
    {
        $language_id = $language ? $language->getLanguageID() : Registry::language()->getLanguageID();
        $order_id = $orderInfo['order_id'];
        $order_product_id = $data['order_product_id'];
        $product_id = (int)$data['product_id'];

        if (!$product_id || !$order_id) {
            return false;
        }
        /**
         * @var Product $product
         */
        $product = Product::with('description')->find($product_id);
        $product_info = $product->toArray();
        $elements_with_options = HtmlElementFactory::getElementsWithOptions();

        if (isset($data['product'])) {
            foreach ($data['product'] as $orderProduct) {
                if ($orderProduct['quantity'] <= 0) { // stupid situation
                    return false;
                }
                //check is product exists
                $order_product = OrderProduct::find($order_product_id);
                /*$exists = $this->db->query(
                    "SELECT op.product_id, op.quantity
                     FROM ".$this->db->table_name("order_products")." op
                     WHERE op.order_id = '".(int)$order_id."'
                            AND op.product_id='".(int)$product_id."'
                            AND op.order_product_id = '".(int)$order_product_id."'");*/

                if ($order_product) {
                    //update order quantity
                    $old_qnt = $order_product->quantity;
                    $order_product->update(
                        [
                            'price'    => H::preformatFloat($orderProduct['price'], $language->get('decimal_point'))
                                / $orderInfo['value'],
                            'total'    => H::preformatFloat($orderProduct['total'], $language->get('decimal_point'))
                                / $orderInfo['value'],
                            'quantity' => $orderProduct['quantity'],
                        ]
                    );

                    /* $sql = "UPDATE ".$this->db->table_name("order_products")."
                             SET price = '".$this->db->escape(
                                         (H::preformatFloat($product['price'],$this->language->get('decimal_point'))
                                             / $orderInfo['value']))."',
                                 total = '".$this->db->escape(
                                     (H::preformatFloat($product['total'],$this->language->get('decimal_point'))
                                             / $orderInfo['value'])
                                 )."',
                                 quantity = '".$this->db->escape($product['quantity'])."'
                             WHERE order_id = '".(int)$order_id."' AND order_product_id = '".(int)$order_product_id."'";
                     $this->db->query($sql);*/
                    //update stock quantity

                    $stock_qnt = $product_info['quantity'];
                    $qnt_diff = $old_qnt - $orderProduct['quantity'];

                    if ($qnt_diff != 0) {
                        if ($qnt_diff < 0) {
                            $new_qnt = $stock_qnt - abs($qnt_diff);
                        } else {
                            $new_qnt = $stock_qnt + $qnt_diff;
                        }
                        if ($product_info['subtract']) {
                            $product->update(
                                [
                                    'quantity' => $new_qnt,
                                ]
                            );
                            /* $sql = "UPDATE ".$this->db->table_name("products")."
                                     SET quantity = '".$new_qnt."'
                                     WHERE product_id = '".(int)$product_id."' AND subtract = 1";
                             $this->db->query($sql);*/
                        }
                    }

                } else {
                    // add new product into order
                    /*$sql = "SELECT *, p.product_id
                            FROM ".$this->db->table_name("products")." p
                            LEFT JOIN ".$this->db->table_name("product_descriptions")." pd
                                ON 
                                (p.product_id = pd.product_id 
                                    AND pd.language_id=".$this->language->getContentLanguageID()
                                .")
                            WHERE p.product_id='".(int)$product_id."'";
                    $product_query = $this->db->query($sql);*/

                    $order_product = new OrderProduct(
                        [
                            'order_id'   => $order_id,
                            'product_id' => $product_id,
                            'name'       => $product->description->name,
                            'model'      => $product->model,
                            'sku'        => $product->sku,
                            'price'      => H::preformatFloat($orderProduct['price'], $language->get('decimal_point'))
                                / $orderInfo['value'],
                            'total'      => H::preformatFloat($orderProduct['total'], $language->get('decimal_point'))
                                / $orderInfo['value'],
                            'quantity'   => $orderProduct['quantity'],
                        ]
                    );
                    $order_product->save();
                    $order_product_id = $order_product->order_product_id;

                    /*$sql = "INSERT INTO ".$this->db->table_name("order_products")."
                            SET order_id = '".(int)$order_id."',
                                product_id = '".(int)$product_id."',
                                name = '".$this->db->escape($product_query->row['name'])."',
                                model = '".$this->db->escape($product_query->row['model'])."',
                                sku = '".$this->db->escape($product_query->row['sku'])."',
                                price = '".$this->db->escape((H::preformatFloat($orderProduct['price'],
                                $this->language->get('decimal_point')) / $orderInfo['value']))."',
                                total = '".$this->db->escape((H::preformatFloat($orderProduct['total'],
                                $this->language->get('decimal_point')) / $orderInfo['value']))."',
                                quantity = '".(int)$orderProduct['quantity']."'";
                    $this->db->query($sql);
                    $order_product_id = $this->db->getLastId();*/

                    //update stock quantity
                    $qnt_diff = -$orderProduct['quantity'];
                    $stock_qnt = $product->quantity;
                    $new_qnt = $stock_qnt - (int)$orderProduct['quantity'];

                    if ($product_info['subtract']) {
                        $product->update(
                            [
                                'quantity' => $new_qnt,
                            ]
                        );
                        /*  $this->db->query("UPDATE ".$this->db->table_name("products")."
                                            SET quantity = '".$new_qnt."'
                                            WHERE product_id = '".(int)$product_id."' AND subtract = 1");*/
                    }
                }

                if ($orderProduct['option']) {
                    //first of all find previous order options
                    // if empty result - order products just added
                    $order_product_options = OrderProduct::getOrderProductOptions($order_id, $order_product_id);

                    $prev_subtract_options = []; //array with previous option values with enabled stock tracking
                    foreach ($order_product_options as $old_value) {
                        if (!$old_value['subtract']) {
                            continue;
                        }
                        $prev_subtract_options[(int)$old_value['product_option_id']][] =
                            (int)$old_value['product_option_value_id'];
                    }

                    $option_types = $po_ids = [];
                    foreach ($orderProduct['option'] as $k => $option) {
                        $po_ids[] = (int)$k;
                    }
                    //get all data of given product options from db
                    ProductOption::setCurrentLanguageID($language_id);
                    $product_options_list = ProductOption::getProductOptionsByIds($po_ids);

                    /*$sql = "SELECT *,
                                    pov.product_option_value_id, 
                                    povd.name AS option_value_name, 
                                    pod.name AS option_name
                            FROM ".$this->db->table_name('product_options')." po
                            LEFT JOIN ".$this->db->table_name('product_option_descriptions')." pod
                                ON (pod.product_option_id = po.product_option_id 
                                    AND pod.language_id=".$this->language->getContentLanguageID().")
                            LEFT JOIN ".$this->db->table_name('product_option_values')." pov
                                ON po.product_option_id = pov.product_option_id
                            LEFT JOIN ".$this->db->table_name('product_option_value_descriptions')." povd
                                ON (povd.product_option_value_id = pov.product_option_value_id 
                                    AND povd.language_id=".$this->language->getContentLanguageID().")
                            WHERE po.product_option_id IN (".implode(',', $po_ids).")
                            ORDER BY po.product_option_id";
                    $result = $this->db->query($sql);*/

                    //list of option value that we do not re-save
                    $exclude_list = [];
                    $option_value_info = [];
                    foreach ($product_options_list as $row) {
                        //skip files
                        if (in_array($row->element_type, ['U'])) {
                            $exclude_list[] = (int)$row->product_option_value_id;
                        }
                        //compound key for cases when val_id is null
                        $option_value_info[$row->product_option_id.'_'.$row->product_option_value_id] = $row;
                        $option_types[$row->product_option_id] = $row->element_type;
                    }

                    //delete old options and then insert new
                    $query = OrderOption::where(
                        [
                            'order_id'         => $order_id,
                            'order_product_id' => $order_product_id,
                        ]
                    );
                    if ($exclude_list) {
                        $query->whereNotIn('product_option_value_id', $exclude_list);
                    }
                    $query->forceDelete();

                    /*$sql = "DELETE FROM ".$this->db->table_name('order_options')."
                            WHERE order_id = ".$order_id." AND order_product_id=".(int)$order_product_id;
                    if ($exclude_list) {
                        $sql .= " AND product_option_value_id NOT IN (".implode(', ', $exclude_list).")";
                    }
                    $this->db->query($sql);*/

                    foreach ($orderProduct['option'] as $opt_id => $values) {

                        if (!is_array($values)) { // for non-multioptional elements
                            //do not save empty input and textarea
                            if (in_array($option_types[$opt_id], ['I', 'T']) && $values == '') {
                                continue;
                            } elseif ($option_types[$opt_id] == 'S') {
                                $values = [$values];
                            } else {
                                foreach ($option_value_info as $o) {
                                    if ($o['product_option_id'] == $opt_id) {
                                        if (!in_array($option_types[$opt_id], $elements_with_options)) {
                                            $option_value_info[$o['product_option_id'].'_'
                                            .$o['product_option_value_id']]['option_value_name'] = $values;
                                        }
                                        $values = [$o['product_option_value_id']];
                                        break;
                                    }
                                }
                            }
                        }

                        $curr_subtract_options = [];
                        foreach ($values as $value) {
                            $arr_key = $opt_id.'_'.$value;
                            $orderOption = new OrderOption(
                                [
                                    'order_id'                => $order_id,
                                    'order_product_id'        => $order_product_id,
                                    'product_option_value_id' => $value,
                                    'name'                    => $option_value_info[$arr_key]['option_name'],
                                    'sku'                     => $option_value_info[$arr_key]['sku'],
                                    'value'                   => $option_value_info[$arr_key]['option_value_name'],
                                    'price'                   => $option_value_info[$arr_key]['price'],
                                    'prefix'                  => $option_value_info[$arr_key]['prefix'],
                                ]
                            );
                            $orderOption->save();

                            /* $sql = "INSERT INTO ".$this->db->table_name('order_options')."
                                     (`order_id`,
                                     `order_product_id`,
                                     `product_option_value_id`,
                                     `name`,
                                     `sku`,
                                     `value`,
                                     `price`,
                                     `prefix`)
                                 VALUES ('".$order_id."',
                                         '".(int)$order_product_id."',
                                         '".(int)$value."',
                                         '".$this->db->escape($option_value_info[$arr_key]['option_name'])."',
                                         '".$this->db->escape($option_value_info[$arr_key]['sku'])."',
                                         '".$this->db->escape($option_value_info[$arr_key]['option_value_name'])."',
                                         '".$this->db->escape($option_value_info[$arr_key]['price'])."',
                                         '".$this->db->escape($option_value_info[$arr_key]['prefix'])."')";

                             $this->db->query($sql);*/

                            if ($option_value_info[$arr_key]['subtract']) {
                                $curr_subtract_options[(int)$opt_id][] = (int)$value;
                            }
                        }

                        //reduce product quantity for option value that not assigned to product anymore
                        $prev_arr = H::has_value($prev_subtract_options[$opt_id])
                            ? $prev_subtract_options[$opt_id]
                            : [];
                        $curr_arr = H::has_value($curr_subtract_options[$opt_id])
                            ? $curr_subtract_options[$opt_id]
                            : [];

                        if ($prev_arr || $curr_arr) {

                            //increase qnt for old option values
                            foreach ($prev_arr as $v) {
                                if (!in_array($v, $curr_arr)) {
                                    $productOptionValue = ProductOptionValue::find($v);
                                    if ($productOptionValue->subtract == 1) {
                                        $productOptionValue->update(
                                            [
                                                'quantity' => ($productOptionValue->quantity
                                                    + $orderProduct['quantity']),
                                            ]
                                        );
                                    }
                                    /* $sql = "UPDATE ".$this->db->table_name("product_option_values")."
                                           SET quantity = (quantity + ".$orderProduct['quantity'].")
                                           WHERE product_option_value_id = '".(int)$v."'
                                                 AND subtract = 1";

                                     $this->db->query($sql);*/
                                }
                            }

                            //decrease qnt for new option values
                            foreach ($curr_arr as $v) {
                                if (!in_array($v, $prev_arr)) {
                                    $productOptionValue = ProductOptionValue::find($v);
                                    if ($productOptionValue->subtract == 1) {
                                        $productOptionValue->update(
                                            [
                                                'quantity' => ($productOptionValue->quantity
                                                    - $orderProduct['quantity']),
                                            ]
                                        );
                                    }
                                    /*$sql = "UPDATE ".$this->db->table_name("product_option_values")."
                                          SET quantity = (quantity - ".$orderProduct['quantity'].")
                                          WHERE product_option_value_id = '".(int)$v."'
                                                AND subtract = 1";

                                    $this->db->query($sql);*/
                                }
                            }

                            //if qnt changed for the same option values
                            $intersect = array_intersect($curr_arr, $prev_arr);
                            if ($intersect && $qnt_diff != 0) {
                                foreach ($intersect as $v) {
                                    $productOptionValue = ProductOptionValue::find($v);
                                    if ($productOptionValue->subtract == 1) {
                                        if ($qnt_diff < 0) {
                                            $newQnt = $productOptionValue->quantity - abs($qnt_diff);
                                        } else {
                                            $newQnt = $productOptionValue->quantity + abs($qnt_diff);
                                        }
                                        $productOptionValue->update(
                                            [
                                                'quantity' => $newQnt,
                                            ]
                                        );
                                    }

                                    /* $sql = "UPDATE ".$this->db->table_name("product_option_values")."
                                           SET quantity = ".$sql_incl."
                                           WHERE product_option_value_id = '".(int)$v."'
                                                 AND subtract = 1";
                                     $this->db->query($sql);*/
                                }
                            }
                        }
                    }
                }//end processing options

            }
        }

        //fix order total and subtotal
        /*
                $sql = "SELECT SUM(total) AS subtotal
                        FROM ".$this->db->table_name('order_products')."
                        WHERE order_id=".$order_id;
                $result = $this->db->query($sql);
                $subtotal = $result->row['subtotal'];
                $text = Registry::currency()->format($subtotal, $orderInfo['currency'], $orderInfo['value']);

                $orderSubtotal =
                $sql = "UPDATE ".$this->db->table_name('order_totals')."
                        SET `value`='".$subtotal."', `text` = '".$text."'
                        WHERE order_id=".$order_id." AND type='subtotal'";
                $this->db->query($sql);

                $sql = "SELECT SUM(`value`) AS total
                        FROM ".$this->db->table_name('order_totals')."
                        WHERE order_id=".$order_id." AND type<>'total'";
                $result = $this->db->query($sql);
                $total = $result->row['total'];
                $text = $this->currency->format($total, $orderInfo['currency'], $orderInfo['value']);

                $sql = "UPDATE ".$this->db->table_name('order_totals')."
                        SET `value`='".$total."', `text` = '".$text."'
                        WHERE order_id=".$order_id." AND type='total'";
                $this->db->query($sql);

                $this->cache->remove('product');
        */
        return true;
    }

}
