<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2018 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/

namespace abc\core\lib;

use abc\core\ABC;
use abc\core\engine\ALanguage;
use abc\core\engine\Registry;
use abc\models\catalog\Product;
use abc\models\catalog\ProductOptionValue;
use abc\models\customer\Address;
use abc\models\locale\Country;
use abc\models\locale\ZoneDescription;
use abc\models\order\Order;
use abc\models\order\OrderDataType;
use abc\models\order\OrderDatum;
use abc\models\order\OrderHistory;
use abc\models\order\OrderOption;
use abc\models\order\OrderProduct;
use abc\models\order\OrderStatus;
use abc\models\order\OrderTotal;
use abc\modules\events\ABaseEvent;
use H;
use Illuminate\Support\Carbon;

/**
 * Class AOrder
 *
 * @property ACart $cart
 * @property AConfig $config
 * @property ATax $tax
 * @property ACurrency $currency
 * @property ARequest $request
 * @property \abc\core\engine\ALoader $load
 * @property ASession $session
 * @property \abc\core\engine\ExtensionsAPI $extensions
 * @property \abc\models\storefront\ModelCheckoutExtension $model_checkout_extension
 * @property AIM $im
 *
 */
class AOrder extends ALibBase
{
    /**
     * @var \abc\core\engine\Registry
     */
    protected $registry;
    /**
     * @var int
     */
    protected $customer_id;
    /**
     * @var int
     */
    protected $order_id;
    /**
     * @var ACustomer
     */
    protected $customer;
    protected $order_data;
    /**
     * @var array public property. needs to use inside hooks
     */
    public $data = [];

    /**
     * AOrder constructor.
     *
     * @param \abc\core\engine\Registry $registry
     * @param string $order_id
     *
     */
    public function __construct($registry, $order_id = '')
    {
        $this->registry = $registry;

        //if nothing is passed use session array. Customer session, can function on storefront only
        if (!H::has_value($order_id)) {
            $this->order_id = (int)$this->session->data['order_id'];
        } else {
            $this->order_id = (int)$order_id;
        }

        if (is_object($this->registry->get('customer'))) {
            $this->customer = $this->registry->get('customer');
            $this->customer_id = $this->customer->getId();
        } else {
            $this->customer = ABC::getObjectByAlias('ACustomer', [$registry]);
        }
    }

    public function __get($key)
    {
        return $this->registry->get($key);
    }

    public function __set($key, $value)
    {
        $this->registry->set($key, $value);
    }

    /**
     * @param int $order_id
     * @param int|string $order_status_id
     *
     * @return array
     * @throws AException
     */
    public function loadOrderData($order_id, $order_status_id = '')
    {
        if ($order_id) {
            $this->order_id = $order_id;
        }
        //get order details for specific status. NOTE: Customer ID need to be set in customer class
        $this->order_data = Order::getOrderArray($this->order_id, $order_status_id);
        $this->extensions->hk_ProcessData($this, 'load_order_data');
        $output = (array)$this->data + (array)$this->order_data;
        return $output;
    }

    /**
     * @param array $indata : Session data array
     *
     * @return array
     * NOTE: method to create an order based on provided data array.
     * @throws AException
     * @throws \ReflectionException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function buildOrderData($indata)
    {
        $order_info = [];
        if (empty($indata)) {
            return [];
        }

        $total_data = [];
        $total = 0;
        $taxes = $this->cart->getTaxes();

        $this->registry->get('load')->model('checkout/extension', 'storefront');

        $sort_order = [];

        $results = $this->registry->get('model_checkout_extension')->getExtensions('total');

        foreach ($results as $key => $value) {
            $sort_order[$key] = $this->config->get($value['key'].'_sort_order');
        }

        array_multisort($sort_order, SORT_ASC, $results);

        foreach ($results as $result) {
            $this->registry->get('load')->model('total/'.$result['key'], 'storefront');
            $this->registry->get('model_total_'.$result['key'])->getTotal($total_data, $total, $taxes, $indata);
        }

        $sort_order = [];

        foreach ($total_data as $key => $value) {
            $sort_order[$key] = $value['sort_order'];
        }

        array_multisort($sort_order, SORT_ASC, $total_data);

        $order_info['store_id'] = $this->config->get('config_store_id');
        $order_info['store_name'] = $this->config->get('store_name');
        $order_info['store_url'] = $this->config->get('config_url');
        //prepare data with customer details.
        if ($this->customer->getId()) {
            $order_info['customer_id'] = $this->customer->getId();
            $order_info['customer_group_id'] = $this->customer->getCustomerGroupId();
            $order_info['firstname'] = (string)$this->customer->getFirstName();
            $order_info['lastname'] = (string)$this->customer->getLastName();
            $order_info['email'] = (string)$this->customer->getEmail();
            $order_info['telephone'] = (string)$this->customer->getTelephone();
            $order_info['fax'] = (string)$this->customer->getFax();

            if ($this->cart->hasShipping()) {
                $shipping_address = [];
                $address = Address::find($indata['shipping_address_id']);
                if ($address) {
                    $shipping_address = $address->toArray();
                    $shipping_address['zone'] = (string) ZoneDescription::where(
                        [
                            'zone_id'     => $shipping_address['zone_id'],
                            'language_id' => Registry::language()->getContentLanguageID(),
                        ]
                    )->first()->name;
                    $country = Country::with('description')->find($shipping_address['country_id']);
                    $shipping_address['country'] = (string)$country->description->name;
                    $shipping_address['address_format'] = (string)$country->address_format;

                }

                $order_info['shipping_firstname'] = (string)$shipping_address['firstname'];
                $order_info['shipping_lastname'] = (string)$shipping_address['lastname'];
                $order_info['shipping_company'] = (string)$shipping_address['company'];
                $order_info['shipping_address_1'] = (string)$shipping_address['address_1'];
                $order_info['shipping_address_2'] = (string)$shipping_address['address_2'];
                $order_info['shipping_city'] = (string)$shipping_address['city'];
                $order_info['shipping_postcode'] = (string)$shipping_address['postcode'];
                $order_info['shipping_zone'] = (string)$shipping_address['zone'];
                $order_info['shipping_zone_id'] = $shipping_address['zone_id'];
                $order_info['shipping_country'] = (string)$shipping_address['country'];
                $order_info['shipping_country_id'] = $shipping_address['country_id'];
                $order_info['shipping_address_format'] = (string)$shipping_address['address_format'];
            } else {
                $order_info['shipping_firstname'] = '';
                $order_info['shipping_lastname'] = '';
                $order_info['shipping_company'] = '';
                $order_info['shipping_address_1'] = '';
                $order_info['shipping_address_2'] = '';
                $order_info['shipping_city'] = '';
                $order_info['shipping_postcode'] = '';
                $order_info['shipping_zone'] = '';
                $order_info['shipping_zone_id'] = '';
                $order_info['shipping_country'] = '';
                $order_info['shipping_country_id'] = '';
                $order_info['shipping_address_format'] = '';
                $order_info['shipping_method'] = '';
            }

            $payment_address = [];
            $address = Address::find($indata['payment_address_id']);
            if ($address) {
                $payment_address = $address->toArray();
                $payment_address['zone'] = (string)ZoneDescription::where(
                    [
                        'zone_id'     => $payment_address['zone_id'],
                        'language_id' => Registry::language()->getContentLanguageID(),
                    ]
                )->first()->name;

                $country = Country::with('description')->find($payment_address['country_id']);
                $payment_address['country'] = (string)$country->description->name;
                $payment_address['address_format'] = (string)$country->address_format;
            }

            $order_info['payment_firstname'] = (string)$payment_address['firstname'];
            $order_info['payment_lastname'] = (string)$payment_address['lastname'];
            $order_info['payment_company'] = (string)$payment_address['company'];
            $order_info['payment_address_1'] = (string)$payment_address['address_1'];
            $order_info['payment_address_2'] = (string)$payment_address['address_2'];
            $order_info['payment_city'] = (string)$payment_address['city'];
            $order_info['payment_postcode'] = (string)$payment_address['postcode'];
            $order_info['payment_zone'] = (string)$payment_address['zone'];
            $order_info['payment_zone_id'] = $payment_address['zone_id'];
            $order_info['payment_country'] = (string)$payment_address['country'];
            $order_info['payment_country_id'] = $payment_address['country_id'];
            $order_info['payment_address_format'] = (string)$payment_address['address_format'];
        } else {
            if (isset($indata['guest'])) {
                //this is a guest order
                $order_info['customer_id'] = 0;
                $order_info['customer_group_id'] = $this->config->get('config_customer_group_id');
                $order_info['firstname'] = (string)$indata['guest']['firstname'];
                $order_info['lastname'] = (string)$indata['guest']['lastname'];
                $order_info['email'] = (string)$indata['guest']['email'];
                $order_info['telephone'] = (string)$indata['guest']['telephone'];
                $order_info['fax'] = (string)$indata['guest']['fax'];

                //IM addresses
                $protocols = $this->im->getProtocols();
                foreach ($protocols as $protocol) {
                    if (H::has_value($indata['guest'][$protocol])
                        && !H::has_value($order_info[$protocol])) {
                        $order_info[$protocol] = $indata['guest'][$protocol];
                    }
                }

                if ($this->cart->hasShipping()) {
                    if (isset($indata['guest']['shipping'])) {
                        $order_info['shipping_firstname'] = (string)$indata['guest']['shipping']['firstname'];
                        $order_info['shipping_lastname'] = (string)$indata['guest']['shipping']['lastname'];
                        $order_info['shipping_company'] = (string)$indata['guest']['shipping']['company'];
                        $order_info['shipping_address_1'] = (string)$indata['guest']['shipping']['address_1'];
                        $order_info['shipping_address_2'] = (string)$indata['guest']['shipping']['address_2'];
                        $order_info['shipping_city'] = (string)$indata['guest']['shipping']['city'];
                        $order_info['shipping_postcode'] = (string)$indata['guest']['shipping']['postcode'];
                        $order_info['shipping_zone'] = (string)$indata['guest']['shipping']['zone'];
                        $order_info['shipping_zone_id'] = $indata['guest']['shipping']['zone_id'];
                        $order_info['shipping_country'] = (string)$indata['guest']['shipping']['country'];
                        $order_info['shipping_country_id'] = $indata['guest']['shipping']['country_id'];
                        $order_info['shipping_address_format'] = (string)$indata['guest']['shipping']['address_format'];
                    } else {
                        $order_info['shipping_firstname'] = (string)$indata['guest']['firstname'];
                        $order_info['shipping_lastname'] = (string)$indata['guest']['lastname'];
                        $order_info['shipping_company'] = (string)$indata['guest']['company'];
                        $order_info['shipping_address_1'] = (string)$indata['guest']['address_1'];
                        $order_info['shipping_address_2'] = (string)$indata['guest']['address_2'];
                        $order_info['shipping_city'] = (string)$indata['guest']['city'];
                        $order_info['shipping_postcode'] = (string)$indata['guest']['postcode'];
                        $order_info['shipping_zone'] = (string)$indata['guest']['zone'];
                        $order_info['shipping_zone_id'] = $indata['guest']['zone_id'];
                        $order_info['shipping_country'] = (string)$indata['guest']['country'];
                        $order_info['shipping_country_id'] = $indata['guest']['country_id'];
                        $order_info['shipping_address_format'] = (string)$indata['guest']['address_format'];
                    }
                } else {
                    $order_info['shipping_firstname'] = '';
                    $order_info['shipping_lastname'] = '';
                    $order_info['shipping_company'] = '';
                    $order_info['shipping_address_1'] = '';
                    $order_info['shipping_address_2'] = '';
                    $order_info['shipping_city'] = '';
                    $order_info['shipping_postcode'] = '';
                    $order_info['shipping_zone'] = '';
                    $order_info['shipping_zone_id'] = '';
                    $order_info['shipping_country'] = '';
                    $order_info['shipping_country_id'] = '';
                    $order_info['shipping_address_format'] = '';
                    $order_info['shipping_method'] = '';
                }

                $order_info['payment_firstname'] = (string)$indata['guest']['firstname'];
                $order_info['payment_lastname'] = (string)$indata['guest']['lastname'];
                $order_info['payment_company'] = (string)$indata['guest']['company'];
                $order_info['payment_address_1'] = (string)$indata['guest']['address_1'];
                $order_info['payment_address_2'] = (string)$indata['guest']['address_2'];
                $order_info['payment_city'] = (string)$indata['guest']['city'];
                $order_info['payment_postcode'] = (string)$indata['guest']['postcode'];
                $order_info['payment_zone'] = (string)$indata['guest']['zone'];
                $order_info['payment_zone_id'] = $indata['guest']['zone_id'];
                $order_info['payment_country'] = (string)$indata['guest']['country'];
                $order_info['payment_country_id'] = $indata['guest']['country_id'];
                $order_info['payment_address_format'] = (string)$indata['guest']['address_format'];

            } else {
                return [];
            }
        }

        if (isset($indata['shipping_method']['title'])) {
            $order_info['shipping_method'] = (string)$indata['shipping_method']['title'];
            // note - id by mask method_txt_id.method_option_id. for ex. default_weight.default_weight_1
            $order_info['shipping_method_key'] = (string)$indata['shipping_method']['id'];
        } else {
            $order_info['shipping_method'] = '';
            $order_info['shipping_method_key'] = '';
        }

        if (isset($indata['payment_method']['title'])) {
            $order_info['payment_method'] = (string)$indata['payment_method']['title'];
            preg_match('/^([^.]+)/', $indata['payment_method']['id'], $matches);
            $order_info['payment_method_key'] = (string)$matches[1];
        } else {
            $order_info['payment_method'] = '';
        }

        $product_data = [];

        foreach ($this->cart->getProducts() as $key => $product) {
            $product_data[$key] = $product;
            $product_data[$key]['key'] = $key;
            $product_data[$key]['name'] = (string)$product_data[$key]['name'];
            $product_data[$key]['model'] = (string)$product_data[$key]['model'];
            $product_data[$key]['sku'] = (string)$product_data[$key]['sku'];
            $product_data[$key]['tax'] = $this->tax->calcTotalTaxAmount($product['total'], $product['tax_class_id']);
            $product_data[$key]['order_status_id'] = (int)$indata['order_status_id'];
        }

        $order_info['products'] = $product_data;
        $order_info['totals'] = $total_data;
        $order_info['comment'] = (string)$indata['comment'];
        $order_info['total'] = $total;
        $order_info['language_id'] = $this->config->get('storefront_language_id');
        $order_info['currency_id'] = $this->currency->getId();
        $order_info['currency'] = $this->currency->getCode();
        $order_info['value'] = $this->currency->getValue($this->currency->getCode());

        if (isset($indata['coupon'])) {
            /**
             * @var APromotion $promotion
             */
            $promotion = ABC::getObjectByAlias('APromotion');
            $coupon = $promotion->getCouponData($indata['coupon']);
            if ($coupon) {
                $order_info['coupon_id'] = $coupon['coupon_id'];
            } else {
                $order_info['coupon_id'] = null;
            }
        } else {
            $order_info['coupon_id'] = null;
        }

        $order_info['ip'] = $this->request->getRemoteIP();

        $this->order_data = $order_info;

        $this->extensions->hk_ProcessData($this, 'build_order_data', $order_info);
        // merge two arrays. $this-> data can be changed by hooks.
        $output = $this->data + $this->order_data;

        return $output;
    }

    /**
     * @return array
     */
    public function getOrderData()
    {
        $this->extensions->hk_ProcessData($this, 'get_order_data');
        $output = $this->data + $this->order_data;
        return $output;
    }

    /**
     * @return int|null
     */
    public function saveOrder()
    {
        if (empty($this->order_data)) {
            return null;
        }
        $this->extensions->hk_ProcessData($this, 'save_order');
        $output = $this->data + $this->order_data;
        $this->order_id = $this->create($output, $this->order_id);
        return (int)$this->order_id;
    }

    /**
     * @return int
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * @return int
     */
    public function getCustomerId()
    {
        return $this->customer_id;
    }

    /**
     * @param $data
     * @param null $set_order_id
     *
     * @return null
     */
    public function create($data, $set_order_id = null)
    {
        $result = $this->extensions->hk_create($this, $data, $set_order_id);
        if ($result !== null) {
            return $result;
        }
        return null;
    }

    /**
     * @param $data
     * @param string $set_order_id
     *
     * @return int
     * @throws AException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function _create($data, $set_order_id = '')
    {
        $settings = Registry::config();
        $dcrypt = Registry::dcrypt();

        $set_order_id = (int)$set_order_id;
        //reuse same order_id or unused one order_status_id = 0
        if ($set_order_id) {
            $order = Order::where(
                [
                    'order_id'        => $set_order_id,
                    'order_status_id' => 0,
                ]
            )->first();

            if (!$order) { // for already processed orders do redirect
                $order = Order::where('order_id', '=', $set_order_id)
                              ->where('order_status_id', '>', 0)
                              ->first();
                if ($order) {
                    return false;
                }
            } //remove
            else {
                //this will remove order with dependencies by foreign keys
                $order->forceDelete();
            }
        }

        //clean up based on setting (remove already created or abandoned orders)
        $expireDays = (int)$settings->get('config_expire_order_days');
        if ($expireDays) {
            try {
                Order::where('order_status_id', '=', 0)
                     ->where(
                         'date_modified',
                         '<',
                         Carbon::now()->subDays($expireDays)->toISOString()
                     )->forceDelete();
            }catch(\Exception $e){
                Registry::log()->write(__FILE__. "Cannot to delete obsolete incomplete orders!\n".$e->getMessage());
            }
        }

        if (!$set_order_id && (int)$settings->get('config_start_order_id')) {
            $maxOrderId = Order::max();
            if ($maxOrderId && $maxOrderId >= $settings->get('config_start_order_id')) {
                $set_order_id = $maxOrderId + 1;
            } elseif ($settings->get('config_start_order_id')) {
                $set_order_id = (int)$settings->get('config_start_order_id');
            } else {
                $set_order_id = 0;
            }
        }

        $insert = $data;
        if ($set_order_id) {
            $insert['order_id'] = $set_order_id;
        } else {
            unset($insert['order_id']);
        }

        if ($dcrypt->active) {
            $insert = $dcrypt->encrypt_data($data, 'orders');
        }

        $order = new Order($insert);
        $order->save();

        $order_id = $order->order_id;

        foreach ($data['products'] as $product) {
            $product['order_id'] = $order_id;
            $product['subtract'] = (int)$product['stock'];
            $orderProduct = new OrderProduct($product);
            $orderProduct->save();
            $order_product_id = $orderProduct->order_product_id;

            foreach ($product['option'] as $option) {
                $option['order_id'] = $order_id;
                $option['order_product_id'] = $order_product_id;
                $orderOption = new OrderOption($option);
                $orderOption->save();
            }

            foreach ($product['download'] as $download) {
                // if expire days not set - 0  as unexpired
                $download['expire_days'] = (int)$download['expire_days'] > 0 ? $download['expire_days'] : 0;
                $download['max_downloads'] = (int)$download['max_downloads']
                    ? (int)$download['max_downloads'] * $product['quantity']
                    : '';
                //disable download for manual mode for customer
                $download['status'] = $download['activate'] == 'manually' ? 0 : 1;
                $download['attributes_data'] = Registry::download()
                                                       ->getDownloadAttributesValues($download['download_id']);

                Registry::download()->addProductDownloadToOrder($order_product_id, $order_id, $download);
            }
        }
        foreach ($data['totals'] as $total) {
            $total['order_id'] = $order_id;
            $total['type'] = $total['total_type'];
            $total['key'] = $total['id'];
            $orderTotal = new OrderTotal($total);
            $orderTotal->save();
        }

        //save IM URI of order
        static::saveIMOrderData($order_id, $data);
        //call event
        H::event('abc\models\storefront\order@create', [new ABaseEvent($order_id, $data)]);
        return $order_id;
    }

    protected static function saveIMOrderData($order_id, $data)
    {
        $settings = Registry::config();
        $im = Registry::im();
        $protocols = $im->getProtocols();

        $orderDataTypes = OrderDataType::whereIn('name', $protocols)->get();
        if (!$orderDataTypes) {
            return false;
        }

        foreach ($orderDataTypes as $row) {
            /**
             * @var OrderDataType $row
             */
            $type_id = $row->type_id;
            if ($data['customer_id']) {
                $uri = $im->getCustomerURI($row->name, $data['customer_id']);
            } else {
                $uri = $data[$row->name];
            }
            if ($uri) {
                $im_data =
                    [
                        'uri'    => $uri,
                        'status' => $settings->get('config_im_guest_'.$row->name.'_status'),
                    ];
                OrderDatum::updateOrCreate(
                    [
                        'order_id' => $order_id,
                        'type_id'  => $type_id,
                        'data'     => $im_data,
                    ]
                );

            }
        }
    }

    /**
     * @param int $order_id
     * @param int $order_status_id
     * @param string $comment
     *
     * @throws \abc\core\lib\AException
     */
    public function confirm($order_id, $order_status_id, $comment = '')
    {
        //trigger an event
        H::event('abc\core\lib\order@beforeConfirm', [new ABaseEvent($order_id, $order_status_id, $comment)]);
        $this->extensions->hk_confirm($this, $order_id, $order_status_id, $comment);
        //trigger an event
        H::event('abc\core\lib\order@afterConfirm', [new ABaseEvent($order_id, $order_status_id, $comment)]);
    }

    /**
     * @param int $order_id
     * @param int $order_status_id
     * @param string $comment
     *
     * @return bool
     * @throws \abc\core\lib\AException
     * @throws \ReflectionException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function _confirm($order_id, $order_status_id, $comment = '')
    {
        //get only incomplete order (order_status_id = 0)
        $orderData = Order::getOrderArray($order_id, 0);
        $orderProducts = OrderProduct::where('order_id', '=', $order_id)->get();

        if (!$orderData || !$orderProducts) {
            return false;
        }

        //update order status
        $order = Order::find($order_id);
        $order->update(
            [
                'order_status_id' => $order_status_id,
            ]
        );

        //record history
        $orderHistory = new OrderHistory(
            [
                'order_id'        => $order_id,
                'order_status_id' => $order_status_id,
                'notify'          => true,
                'comment'         => $comment,
            ]
        );
        $orderHistory->save();
        $orderData['comment'] = $orderData['comment'].' '.$comment;

        // load language for IM
        $language = new ALanguage($this->registry, $orderData['code']);
        $language->load($language->language_details['directory']);
        $language->load('common/im');

        //update products inventory
        foreach ($orderProducts as $product) {
            $orderOptions = OrderOption::where(
                [
                    'order_id'         => $order_id,
                    'order_product_id' => $product->order_product_id,
                ]
            )->get();

            $product->update(
                [
                    'order_status_id' => $order_status_id,
                ]
            );

            //update options stock
            $stock_updated = false;
            foreach ($orderOptions as $option) {
                $productOptionValues = ProductOptionValue::where(
                    [
                        'product_option_value_id' => $option['product_option_value_id'],
                        'subtract'                => 1,
                    ]
                );
                $productOptionValues->decrement('quantity', (int)$product['quantity']);

                $stock_updated = true;
                $quantityLeft = $productOptionValues->get('quantity');

                if ($quantityLeft <= 0) {
                    //notify admin with out of stock for option based product
                    $message_arr = [
                        1 => [
                            'message' => sprintf($language->get('im_product_out_of_stock_admin_text'),
                                $product['product_id']),
                        ],
                    ];
                    $this->im->send('product_out_of_stock', $message_arr);
                }
            }

            if (!$stock_updated) {
                $stockProduct = Product::where(
                    [
                        'product_id' => $product['product_id'],
                        'subtract'   => 1,
                    ]
                );
                $stockProduct->decrement('quantity', (int)$product['quantity']);

                //check quantity and send notification when 0 or less
                if ($stockProduct->get('quantity') <= 0) {
                    //notify admin with out of stock
                    $message_arr = [
                        1 => [
                            'message' => sprintf($language->get('im_product_out_of_stock_admin_text'),
                                $product['product_id']),
                        ],
                    ];
                    $this->im->send('product_out_of_stock', $message_arr);
                }
            }
        }

        //clean product cache as stock might have changed.
        Registry::cache()->flush('product');

        H::event('storefront\sendOrderConfirmEmail', [new ABaseEvent($orderData)]);
        return true;
    }

    /**
     * @param int $order_id
     * @param int $order_status_id
     * @param string $comment
     * @param bool|false $notify
     *
     * @throws \abc\core\lib\AException
     */
    public function update($order_id, $order_status_id, $comment = '', $notify = false)
    {
        $this->extensions->hk_update($this, $order_id, $order_status_id, $comment, $notify);
        //call event
        H::event('abc\core\lib\order@update', [new ABaseEvent($order_id, $order_status_id, $comment, $notify)]);
    }

    /**
     * @param $order_id
     * @param $order_status_id
     * @param string $comment
     * @param bool $notify
     *
     * @return bool
     * @throws AException
     * @throws \ReflectionException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function _update($order_id, $order_status_id, $comment = '', $notify = false)
    {
        $orderData = Order::getOrderArray($order_id);
        $orderProducts = OrderProduct::where('order_id', '=', $order_id)->get();

        $orderStatus = OrderStatus::with('description')->find($order_status_id);
        if (!$orderData || !$orderProducts || !$orderStatus) {
            return false;
        }

        //update order status
        $order = Order::find($order_id);
        $order->update(
            [
                'order_status_id' => $order_status_id,
            ]
        );

        //record history
        $orderHistory = new OrderHistory(
            [
                'order_id'        => $order_id,
                'order_status_id' => $order_status_id,
                'notify'          => (int)$notify,
                'comment'         => $comment,
            ]
        );
        $orderHistory->save();

        //send notifications
        // load language for IM
        $language = new ALanguage($this->registry, $orderData['code']);
        $language->load($language->language_details['directory']);
        $language->load('mail/order_update');

        $language_im = new ALanguage($this->registry);
        $language->load($language->language_details['directory']);
        $language_im->load('common/im');

        $message_arr = [
            0 => [
                'message' => sprintf(
                    $language_im->get('im_order_update_text_to_customer'),
                    $order_id,
                    $orderStatus->description->name
                ),
            ],
            1 => [
                'message' => sprintf(
                    $language_im->get('im_order_update_text_to_admin'),
                    $order_id,
                    $orderStatus->description->name
                ),
            ],
        ];
        $this->im->send('order_update', $message_arr);

        //notify via email
        if ($notify) {
            H::event('storefront\sendOrderUpdateEmail', [new ABaseEvent($orderData, $orderStatus, $comment)]);
        }
        return true;
    }

}
