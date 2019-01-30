<?php

namespace abc\models\order;

use abc\models\BaseModel;

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
 * @package abc\models
 */
class Order extends BaseModel
{
    public $timestamps = false;
    protected $primaryKey = 'order_id';

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
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
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

    public function order_data()
    {
        return $this->hasMany(OrderDatum::class, 'order_id');
    }

    public function order_downloads()
    {
        return $this->hasMany(OrderDownload::class, 'order_id');
    }

    public function order_downloads_histories()
    {
        return $this->hasMany(OrderDownloadsHistory::class, 'order_id');
    }

    public function order_products()
    {
        return $this->hasMany(OrderProduct::class, 'order_id');
    }

    public function order_totals()
    {
        return $this->hasMany(OrderTotal::class, 'order_id');
    }
}
