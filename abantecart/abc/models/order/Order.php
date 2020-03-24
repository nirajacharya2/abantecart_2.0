<?php

namespace abc\models\order;

use abc\core\engine\ALanguage;
use abc\core\engine\HtmlElementFactory;
use abc\core\engine\Registry;
use abc\core\lib\ADataEncryption;
use abc\core\lib\ADB;
use abc\core\lib\AException;
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
use abc\modules\events\ABaseEvent;
use Carbon\Carbon;
use Exception;
use H;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Psr\SimpleCache\InvalidArgumentException;

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
 * @property Carbon $date_added
 * @property Carbon $date_modified
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
 * @method static Order find(int $order_id) Order
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
                'nullable',
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
                'nullable',
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

        'value'     => [
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

    /**
     * @var string
     * @see Order::getOrders()
     */
    public static $searchMethod = 'getOrders',
        $searchParams = [
        'filter' => [
            'order_id',
            'customer_id',
            'coupon_id',
            'order_status_id',
            'product_id',
            'customer_name',
            'date_added',
            'date_start',
            'date_end',
            'store_id',
            'total',
        ],
        //pagination
        'sort',
        'order',
        'start',
        'limit',
    ];

    public function setPaymentMethodDataAttribute($value)
    {
        $this->attributes['payment_method_data'] = serialize($value);
    }

    public function setCustomerIdAttribute($value)
    {
        $this->attributes['customer_id'] = empty($value) ? null : (int)$value;
    }

    public function setCouponIdAttribute($value)
    {
        $this->attributes['coupon_id'] = empty($value) ? null : (int)$value;
    }

    public function setShippingZoneIdAttribute($value)
    {
        $this->attributes['shipping_zone_id'] = empty($value) ? null : (int)$value;
    }

    public function setPaymentZoneIdAttribute($value)
    {
        $this->attributes['payment_zone_id'] = empty($value) ? null : (int)$value;
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
        /**
         * @var ADataEncryption $dcrypt
         */
        $dcrypt = Registry::dcrypt();
        if ($dcrypt->active) {
            $data = $dcrypt->encrypt_data($data, 'orders');
        }

        $this->attributes = $data;
        Registry::cache()->flush('order');
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

            $orderProducts = OrderProduct::where('order_id', '=', $this->order_id)->get();
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
                            'order_id'         => $this->order_id,
                            'order_product_id' => $orderProduct['order_product_id'],
                        ]
                    )->get();
                    if ($orderOptions) {
                        foreach ($orderOptions as $orderOption) {
                            /** @var ProductOptionValue $option */
                            $option = ProductOptionValue::find($orderOption['product_option_value_id']);
                            if ($option->subtract) {
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
        Registry::cache()->flush('order');

    }

    /**
     * @param int $order_id
     * @param int|null|string $order_status_id - if NUll - seek greater than 0, 'any' - try to get all statuses, integer - seek specific order status
     * @param int|null $customer_id
     *
     * @return array
     * @throws AException
     */
    public static function getOrderArray($order_id, $order_status_id = null, $customer_id = null)
    {
        $customer_id = (int)$customer_id;
        $order = null;
        try {
            /**
             * @var QueryBuilder $query
             */
            $query = Order::select(
                [
                    'orders.*',
                    'order_status_descriptions.name as order_status_name',
                    'languages.code as language_code',
                    'languages.filename as language_filename',
                ])
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
                        )->on(
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
        } catch (Exception $e) {
            Registry::log()->write(__CLASS__.': '.$e->getMessage());

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
        $query = Order::select(
            [
                'orders.*',
                'order_status_descriptions.name as order_status_name',
            ])
                      ->where('orders.order_status_id', '>', 0)
                      ->leftJoin(
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
                                  )->on(
                                      'order_status_descriptions.language_id',
                                      '=',
                                      'orders.language_id'
                                  );
                          }
                      )
                      ->where('orders.customer_id', '=', $customer_id);
        if ($order_id) {
            $query->where('order_id', '=', $order_id);
        }
        $query->orderByDesc('orders.date_added')
              ->limit($limit)
              ->offset($start);
        Registry::extensions()->hk_extendQuery($this, __FUNCTION__, $query, func_get_args());
        return $query->get()->toArray();
    }

    /**
     * @param $order_id
     * @param $order_product_id
     *
     * @return Collection
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
     * @return Collection
     */
    public static function getOrderHistories($order_id)
    {
        /** @var QueryBuilder $query */
        $query = OrderHistory::select(
            [
                'order_history.*',
                'order_status_descriptions.name AS order_status_name',
            ]
        )->where(
            [
                'order_history.order_id' => $order_id,
                'order_history.notify'   => 1,
            ]
        )->leftJoin(
            'orders',
            'orders.order_id',
            '=',
            'order_history.order_id'
        )->leftJoin(
            'order_status_descriptions',
            function ($join) {
                /**
                 * @var JoinClause $join
                 */
                $join
                    ->on(
                        'order_history.order_status_id',
                        '=',
                        'order_status_descriptions.order_status_id'
                    )->on(
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
     * @throws Exception
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
                if ($uri) {
                    $output[$protocol] = ['uri' => $uri];
                }
            }
        }

        return $output;
    }

    /**
     * @param int $order_id
     * @param array $data
     *
     * @return bool
     * @throws AException
     * @throws InvalidArgumentException
     */
    public static function editOrder(int $order_id, array $data)
    {
        if (!$data || !$order_id) {
            return false;
        }
        $old_language = Product::getCurrentLanguageID();
        Registry::db()->beginTransaction();
        try {
            $order = Order::find($order_id);
            if (!$order) {
                return false;
            }

            if ($data['order_totals']['total']['value'] !== null) {
                $data['total'] = (float)$data['order_totals']['total']['value'];
                $data['total_difference'] = $order->total - $data['total'];
            }

            $order->update($data);

            if (!$data['order_totals']) {
                H::event('abc\models\admin\order@update', [new ABaseEvent($order_id, $data)]);
                Registry::db()->commit();
                return true;
            }

            $orderInfo = Order::getOrderArray($order_id, 'any');
            $language = Language::find($orderInfo['language_id']);
            $oLanguage = new ALanguage(Registry::getInstance(), $language->code);
            $oLanguage->load($language->directory);

            if ($data['product']) {
                Product::setCurrentLanguageID($orderInfo['language_id']);
                static::editOrderProducts($orderInfo, $data, $oLanguage);
            }

            if (!$data['order_totals']) {
                H::event('abc\models\admin\order@update', [new ABaseEvent($order_id, $data)]);
                Registry::db()->commit();
                return true;
            }

            //remove previous totals
            OrderTotal::where('order_id', '=', $order_id)->forceDelete();
            foreach ($data['order_totals'] as $orderTotal) {
                $orderTotal['order_id'] = $order_id;
                $total = new OrderTotal($orderTotal);
                $total->save();
            }

            Registry::db()->commit();
            Registry::cache()->flush('order');
            //revert language for model back
            Product::setCurrentLanguageID($old_language);

            H::event('abc\models\admin\order@update', [new ABaseEvent($order_id, $data)]);
        } catch (Exception $e) {
            Registry::log()->write(__CLASS__.': '.$e->getMessage()."\nTrace: ".$e->getTraceAsString());
            Registry::db()->rollback();
            throw new AException('Error during order saving process. See log for details.');
        }
        return true;
    }

    /**
     * @param array $orderInfo
     * @param array $data
     *
     * @param null|ALanguage $language
     *
     * @return bool
     * @throws Exception
     * @throws InvalidArgumentException
     */

    protected static function editOrderProducts(array $orderInfo, array $data, $language = null)
    {
        $language_id = $language ? $language->getLanguageID() : Registry::language()->getLanguageID();
        $order_id = $orderInfo['order_id'];
        $qnt_diff = 0;

        if (!$order_id) {
            return false;
        }

        $elements_with_options = HtmlElementFactory::getElementsWithOptions();

        if (isset($data['product'])) {
            foreach ($data['product'] as $orderProduct) {
                $order_product_id = $orderProduct['order_product_id'];
                $product_id = (int)$orderProduct['product_id'];
                if ($orderProduct['quantity'] < 0) { // stupid situation
                    continue;
                }

                /**
                 * @var Product $product
                 */
                $product = Product::with('description')->find($product_id);
                $product_info = $product ? $product->toArray() : [];
                //check is product exists
                $order_product = OrderProduct::find($order_product_id);
                if ($order_product) {
                    //update order quantity
                    $old_qnt = $order_product->quantity;
                    $update = $orderProduct;
                    $update['price'] = H::preformatFloat(
                            $orderProduct['price'],
                            $language->get('decimal_point')) / $orderInfo['value'];

                    $update['total'] = H::preformatFloat(
                            $orderProduct['total'],
                            $language->get('decimal_point')) / $orderInfo['value'];

                    $order_product->update($update);

                    //update stock quantity if product presents
                    if ($product_info['quantity'] !== null) {
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
                            }
                        }
                    }
                } else {
                    $new_data = $orderProduct;
                    $new_data['order_id'] = $order_id;
                    $new_data['product_id'] = $product_id;
                    $new_data['name'] = $product->description->name;
                    $new_data['model'] = $product->model;
                    $new_data['sku'] = $product->sku;
                    $new_data['price'] = H::preformatFloat(
                            $orderProduct['price'],
                            $language->get('decimal_point')) / $orderInfo['value'];

                    $new_data['total'] = H::preformatFloat(
                            $orderProduct['total'],
                            $language->get('decimal_point')) / $orderInfo['value'];

                    $order_product = new OrderProduct($new_data);
                    $order_product->save();
                    $order_product_id = $order_product->order_product_id;

                    //update stock quantity
                    $qnt_diff = -$orderProduct['quantity'];
                    $stock_qnt = $product->quantity;
                    $new_qnt = $stock_qnt - (int)$orderProduct['quantity'];
                    //if product presents in database
                    if ($product_info['quantity'] !== null) {
                        if ($product_info['subtract']) {
                            $product->update(
                                [
                                    'quantity' => $new_qnt,
                                ]
                            );
                        }
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

                    $option_types = [];
                    $po_ids = array_keys($orderProduct['option']);

                    //get all data of given product options from db
                    ProductOption::setCurrentLanguageID($language_id);
                    $product_options_list = ProductOption::getProductOptionsByIds($po_ids);

                    //list of option value that we do not re-save
                    $exclude_list = [];
                    $option_value_info = [];
                    foreach ($product_options_list as $row) {
                        //skip files
                        if (in_array($row->element_type, ['U'])) {
                            $exclude_list[] = (int)$row->product_option_value_id;
                        }
                        //compound key for cases when val_id is null
                        $option_value_info[$row->product_option_id.'_'.$row->product_option_value_id] = $row->toArray();
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
                            if (!$value) {
                                continue;
                            }
                            $arr_key = $opt_id.'_'.$value;
                            $optionData = $option_value_info[$arr_key];
                            unset($optionData['date_added'], $optionData['date_modified']);
                            $optionData['order_id'] = $order_id;
                            $optionData['order_product_id'] = $order_product_id;
                            $optionData['product_option_value_id'] = $value;
                            $optionData['name'] = $option_value_info[$arr_key]['option_name'];
                            $optionData['value'] = $option_value_info[$arr_key]['option_value_name'];

                            $orderOption = new OrderOption($optionData);
                            $orderOption->save();

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
                                }
                            }
                        }
                    }
                }//end processing options

            }
        }
        return true;
    }

    public static function getGuestOrdersWithProduct($product_id)
    {
        $product_id = (int)$product_id;
        if (!$product_id) {
            return [];
        }
        /**
         * @var QueryBuilder $query
         */
        $table_name = Registry::db()->table_name('orders');
        $query = OrderProduct::where('order_products.product_id', '=', $product_id)
                             ->whereRaw("COALESCE(".$table_name.".customer_id,0) = 0")
                             ->join(
                                 'orders',
                                 'orders.order_id',
                                 '=',
                                 'order_products.order_id'
                             );

        //allow to extends this method from extensions
        Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $query, func_get_args());
        $query->useCache('order');
        return $query->get();
    }

    /**
     * @param array $inputData
     *
     * @return int|Collection
     * @throws AException
     */
    public static function getOrders($inputData = [])
    {
        $mode = (string)$inputData['mode'];
        $language_id = static::$current_language_id;
        /**
         * @var ADataEncryption $dcrypt
         */
        $dcrypt = Registry::dcrypt();

        $currency = Registry::currency();
        /**
         * @var ADB $db
         */
        $db = Registry::db();
        $aliasO = $db->table_name('orders');
        $aliasOSD = $db->table_name('order_status_descriptions');
        $order = new Order();

        $select = [];
        if ($mode == 'total_only') {
            $select[] = $db->raw('COUNT(*) as total');
        } else {
            $select = [
                $db->raw('CONCAT('.$aliasO.'.firstname, \' \', '.$aliasO.'.lastname) AS name'),
                $db->raw("(SELECT name
                            FROM ".$aliasOSD."
                            WHERE ".$aliasOSD.".order_status_id = ".$aliasO.".order_status_id
                                AND ".$aliasOSD.".language_id = '".(int)$language_id."' LIMIT 1) AS status"),
            ];
        }

        /**
         * @var QueryBuilder $query
         */
        if ($mode != 'total_only') {
            $query = $order->selectRaw($db->raw_sql_row_count().' '.$aliasO.'.*');
        } else {
            $query = $order->select();
        }
        $query->addSelect($select);

        $filter = $inputData['filter'];
        if ($filter['order_status_id'] == 'all') {
            $query->where('orders.order_status_id', '>=', '0');
        } else {
            if (H::has_value($filter['order_status_id'])) {
                $query->where('orders.order_status_id', '=', (int)$filter['order_status_id']);
            } else {
                $query->where('orders.order_status_id', '>', '0');

            }
        }

        if (H::has_value($filter['product_id'])) {
            $query->leftJoin(
                'order_products',
                'orders.order_id',
                '=',
                'order_products.order_id'
            );
            $query->where('order_products.product_id', '=', $filter['product_id']);
        }

        if (H::has_value($inputData['filter']['coupon_id'])) {
            $query->where('orders.coupon_id', '=', $inputData['filter']['coupon_id']);
        }

        if (H::has_value($filter['customer_id'])) {
            $query->where('orders.customer_id', '=', $filter['customer_id']);
        }

        if (H::has_value($filter['order_id'])) {
            $query->where('orders.order_id', '=', $filter['order_id']);
        }

        if (H::has_value($filter['customer_name'])) {
            $query->whereRaw(
                "CONCAT(".$aliasO.".firstname, ' ', ".$aliasO.".lastname) LIKE '%".$filter['customer_name']."%'"
            );
        }

        if (H::has_value($filter['date_added'])) {
            $query->whereRaw(
                "DATE(".$aliasO.".date_added) = DATE('".$db->escape($filter['date_added'])."')"
            );
        }

        if (H::has_value($filter['date_start'])) {
            $query->whereRaw(
                "DATE(".$aliasO.".date_added) >= DATE('".$db->escape($filter['date_start'])."')"
            );
        }

        if (H::has_value($filter['date_end'])) {
            $query->whereRaw(
                "DATE(".$aliasO.".date_added) <= DATE('".$db->escape($filter['date_end'])."')"
            );
        }

        if ($filter['store_id'] !== null) {
            $query->where('orders.store_id', '=', (int)$filter['store_id']);
        }

        if (H::has_value($filter['total'])) {
            $filter['total'] = trim($filter['total']);
            //check if compare signs are used in the request
            $compare = '';
            if (in_array(substr($filter['total'], 0, 2), ['>=', '<='])) {
                $compare = substr($filter['total'], 0, 2);
                $filter['total'] = substr($filter['total'], 2, strlen($filter['total']));
                $filter['total'] = trim($filter['total']);
            } else {
                if (in_array(substr($filter['total'], 0, 1), ['>', '<', '='])) {
                    $compare = substr($filter['total'], 0, 1);
                    $filter['total'] = substr(
                        $filter['total'],
                        1,
                        strlen($filter['total'])
                    );
                    $filter['total'] = trim($filter['total']);
                }
            }

            $filter['total'] = (float)$filter['total'];
            //if we compare, easier select
            if ($compare) {
                $query->whereRaw(
                    "FLOOR(
                            CAST(".$aliasO.".total as DECIMAL(15,4))) ".
                    $compare
                    ."  FLOOR(CAST(".$filter['total']." as DECIMAL(15,4)))");
            } else {
                $currencies = $currency->getCurrencies();
                $temp = $temp2 = [
                    $filter['total'],
                    ceil($filter['total']),
                    floor($filter['total']),
                ];
                foreach ($currencies as $currency1) {
                    foreach ($currencies as $currency2) {
                        if ($currency1['code'] != $currency2['code']) {
                            $temp[] = floor($currency->convert($filter['total'], $currency1['code'],
                                $currency2['code']));
                            $temp2[] = ceil($currency->convert($filter['total'], $currency1['code'],
                                $currency2['code']));
                        }
                    }
                }
                $query->where(function ($query) use ($aliasO, $temp, $temp2) {
                    /**
                     * @var QueryBuilder $query
                     */
                    $query->orWhereRaw("FLOOR(".$aliasO.".total) IN  (".implode(",", $temp).")");
                    $query->orWhereRaw(
                        "FLOOR(
                             CAST(".$aliasO.".total as DECIMAL(15,4)) * CAST(".$aliasO.".value as DECIMAL(15,4))) 
                                  IN  (".implode(",", $temp).")");
                    $query->orWhereRaw("CEIL(".$aliasO.".total) IN  (".implode(",", $temp2).")");
                    $query->orWhereRaw(
                        "CEIL(
                            CAST(".$aliasO.".total as DECIMAL(15,4)) * CAST(".$aliasO.".value as DECIMAL(15,4))) 
                                IN  (".implode(",", $temp2).")");
                });
            }
        }

        //If for total, we done building the query
        if ($mode == 'total_only') {
            //allow to extends this method from extensions
            Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $query, $inputData);
            $result = $query->first();
            return (int)$result->total;
        }

        $sort_data = [
            'order_id'   => 'orders.order_id',
            'name'       => 'name',
            'status'     => 'status',
            'date_added' => 'orders.date_added',
            'total'      => 'orders.total',
        ];

        // NOTE: Performance slowdown might be noticed or larger search results

        $orderBy = $sort_data[$inputData['sort']] ? $sort_data[$inputData['sort']] : 'name';
        if (isset($inputData['order']) && (strtoupper($inputData['order']) == 'DESC')) {
            $sorting = "desc";
        } else {
            $sorting = "asc";
        }

        $query->orderBy($orderBy, $sorting);
        if (isset($inputData['start']) || isset($inputData['limit'])) {
            if ($inputData['start'] < 0) {
                $inputData['start'] = 0;
            }
            if ($inputData['limit'] < 1) {
                $inputData['limit'] = 20;
            }
            $query->offset((int)$inputData['start'])->limit((int)$inputData['limit']);
        }

        //allow to extends this method from extensions
        Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $query, $inputData);
        $query->useCache('order');
        $result_rows = $query->get();

        //finally decrypt data and return result
        $totalNumRows = $db->sql_get_row_count();
        for ($i = 0; $i < $result_rows->count(); $i++) {
            $result_rows[$i] = $dcrypt->decrypt_data($result_rows[$i], 'orders');
            $result_rows[$i]['total_num_rows'] = $totalNumRows;
        }

        return $result_rows;

    }

    /**
     * @param array $customers_ids
     *
     * @return array
     * @throws Exception
     */
    public static function getCountOrdersByCustomerIds($customers_ids)
    {
        $customers_ids = (array)$customers_ids;
        $ids = [];
        foreach ($customers_ids as $cid) {
            $cid = (int)$cid;
            if ($cid) {
                $ids[] = $cid;
            }
        }

        if (!$ids) {
            return [];
        }
        /**
         * @var QueryBuilder $query
         */
        $query = Order::select('customer_id')
                      ->selectRaw('COUNT(*) as count')
                      ->whereIn('customer_id', $ids)
                      ->where('order_status_id', '>', '0')
                      ->groupBy('customer_id');
        //allow to extends this method from extensions
        Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $query, $customers_ids);
        return $query->get()->pluck('count', 'customer_id')->toArray();
    }

}
