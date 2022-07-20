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
use abc\core\engine\Registry;

use abc\models\customer\Address;
use abc\models\storefront\ModelCheckoutExtension;
use abc\modules\events\ABaseEvent;
use H;

/**
 * Class OrderProcessing
 *
 * @package abc\core\lib
 *
 *
 */
class CheckoutBase extends ALibBase
{
    /**
     * @var Registry
     */
    protected $registry;
    /**
     * @var int
     */
    protected $customer_id;
    /**
     * @var AOrder
     */
    protected $order = null;
    /**
     * @var ACart
     */
    public $cart;
    /**
     * @var ACustomer
     */
    public $customer;
    /**
     * @var ATax
     */
    public $tax;
    /**
     * @var array public property. needs to use inside hooks
     */
    public $data = [];

    /**
     * public property for external validation from hooks
     * @var array
     */
    public $errors = [];
    /**
     * @var bool mode that allows to adds and checkout disabled products
     */
    protected $conciergeMode = false;

    /**
     * OrderProcessing constructor.
     *
     * @param $registry
     * @param array $data
     *
     * @throws AException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function __construct(Registry $registry, array $data)
    {
        $this->registry = $registry;

        $this->data = $data;
        if($data['cart']) {
            $this->cart = $data['cart'];
        }
        if($data['customer']) {
            $this->customer = $data['customer'];
            $this->setCustomer($this->customer);
            $this->customer_id = $this->customer->getId();
        }

        if(is_object($data['order'])){
            $this->order = $data['order'];
        }else{
            $order_params = [$this->registry];
            if($data['order_id']){
                $order_params[] = $data['order_id'];
            }
            $this->order = ABC::getObjectByAlias('AOrder', $order_params);
            if($data['order_id']){
                $this->order->loadOrderData($data['order_id']);
            }
        }

    }

    /**
     * @return bool
     */
    public function getConciergeMode()
    {
        return $this->conciergeMode;
    }
    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
    /**
     * @return int
     */
    public function getOrderId()
    {
        return (int)$this->data['order_id'];
    }

    /**
     * @param int $order_id
     *
     * @return void
     */
    public function setOrderId(int $order_id)
    {
        $this->data['order_id'] = $order_id;
    }
    /**
     * @param string $rt
     * @param string $mode
     *
     * @return mixed
     */
    protected function loadModel(string $rt, $mode = '')
    {
        return $this->registry->get('load')->model($rt, $mode);
    }

    /**
     * @return array
     * @throws AException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function getPaymentList()
    {
        if($this->data['guest']){
            $payment_address = $this->data['guest'];
        }else {
            $payment_address = $this->getAddressById($this->data['payment_address_id']);
            if (!$payment_address) {
                $customer_id = $this->data['customer']->getId();
                if ($customer_id) {
                    $address = Address::where('customer_id', '=', $customer_id)->first();
                    if ($address) {
                        $payment_address = $this->getAddressById($address->address_id);
                    }
                }
            }
        }

        if (!$payment_address) {
            ADebug::warning(
                __CLASS__.'->'.__FUNCTION__.'() ::',
                AC_ERR_USER_WARNING,
                'Cannot get payments list. Empty payment address info'
            );
            return [];
        }

        $output = [];
        // If total amount of order is zero - do redirect on confirmation page
        $totalAmount = $this->cart->getTotalAmount(true);
        /**
         * @var ModelCheckoutExtension $modelCheckoutExtension
         */
        $modelCheckoutExtension = $this->loadModel('checkout/extension','storefront');
        $results = $modelCheckoutExtension->getExtensions('payment');
        $acceptedPayments = [];
        //#Check config of selected shipping method and see if we have accepted payments restriction
        $shipping_ext = explode('.', $this->data['shipping_method']['id']);
        $ship_ext_config = $modelCheckoutExtension->getSettings($shipping_ext[0]);
        $accept_payment_ids = $ship_ext_config[$shipping_ext[0]."_accept_payments"];
        if (is_array($accept_payment_ids) && count($accept_payment_ids)) {
            //#filter only allowed payment methods based on shipping
            foreach ($results as $result) {
                if (in_array($result['extension_id'], $accept_payment_ids)) {
                    $acceptedPayments[] = $result;
                }
            }
        } else {
            $acceptedPayments = $results;
        }

        $paymentSettings = [];
        foreach ($acceptedPayments as $result) {
            //filter only allowed payment methods based on total min/max
            $ext_text_id = $result['key'];
            $paymentSettings[$ext_text_id] = $modelCheckoutExtension->getSettings($ext_text_id);
            $min = $paymentSettings[$ext_text_id][$ext_text_id."_payment_minimum_total"];
            $max = $paymentSettings[$ext_text_id][$ext_text_id."_payment_maximum_total"];
            if ((H::has_value($min) && $totalAmount < $min)
                || (H::has_value($max) && $totalAmount > $max)
            ) {
                continue;
            }

            $extModel = $this->loadModel('extension/'.$ext_text_id, 'storefront');
            $paymentMethod = $extModel->getMethod($payment_address);
            if ($paymentMethod) {
                $output[$ext_text_id] = $paymentMethod;
                $output[$ext_text_id]['settings'] = $paymentSettings[$ext_text_id];

                //# Add storefront icon if available
                $icon = $paymentSettings[$ext_text_id][$ext_text_id."_payment_storefront_icon"];
                if (H::has_value($icon)) {
                    $icon_data = $modelCheckoutExtension->getSettingImage($icon);
                    $icon_data['image'] = $icon;
                    $output[$ext_text_id]['icon'] = $icon_data;
                }
                //check if this is a redirect type of the payment
                if ($paymentSettings[$ext_text_id][$ext_text_id."_redirect_payment"]) {
                    $output[$ext_text_id]['is_redirect_payment'] = true;
                }
            }
        }

        return $output;
    }

    /**
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getShippingList()
    {
        $output = [];
        if($this->data['guest']){
            $shipping_address = $this->data['guest']['shipping'] ?: $this->data['guest'];
        }else {
            $shipping_address = $this->getAddressById($this->data['shipping_address_id']);
            if (!$shipping_address) {
                $customer_id = $this->data['customer']->getId();
                if ($customer_id) {
                    $address = Address::where('customer_id', '=', $customer_id)->first();
                    if ($address) {
                        $shipping_address = $this->getAddressById($address->address_id);
                    }
                }
            }
        }
        if (!$shipping_address) {
            ADebug::warning(
                __CLASS__.'->'.__FUNCTION__.'() ::',
                AC_ERR_USER_WARNING,
                'Cannot get shipping list. Empty payment address info'
            );
            return [];
        }

        /**
         * @var ModelCheckoutExtension $modelCheckoutExtension
         */
        $modelCheckoutExtension = $this->loadModel('checkout/extension', 'storefront');
        $results = $modelCheckoutExtension->getExtensions('shipping');
        foreach ($results as $result) {
            $ext_txt_id = $result['key'];
            $extModel = $this->loadModel('extension/'.$ext_txt_id, 'storefront');
            $quote = $extModel->getQuote($shipping_address);

            if ($quote) {
                //# Add storefront icon if available
                $shippingSettings = $modelCheckoutExtension->getSettings($ext_txt_id);
                $output[$ext_txt_id] = [
                    'title'      => $quote['title'],
                    'quote'      => $quote['quote'],
                    'sort_order' => $quote['sort_order'],
                    'error'      => $quote['error'],
                    'settings'   => $shippingSettings
                ];

                $icon = $shippingSettings[$ext_txt_id."_shipping_storefront_icon"];
                if (H::has_value($icon)) {
                    $icon_data = $modelCheckoutExtension->getSettingImage($icon);
                    $icon_data['image'] = $icon;
                    $output[$ext_txt_id]['icon'] = $icon_data;
                }
            }
        }

        $sort_order = [];
        foreach ($output as $key => $value) {
            $sort_order[$key] = $value['sort_order'];
        }

        array_multisort($sort_order, SORT_ASC, $output);

        return $output;
    }

    /**
     * @param int $address_id
     *
     * @return array
     */
    public function getAddressById($address_id)
    {
        if (!$address_id) {
            return [];
        }
        $address = Address::find($address_id);
        if ($address && $address->customer_id == $this->customer_id) {
            return $address->toArray();
        }
        return [];
    }

    /**
     * @param int $address_id
     * @param array $address - required if $address_id zero
     */
    public function setShippingAddress($address_id = 0, $address = [])
    {
        if($address && $this->data['guest']){
            $this->data['guest']['shipping'] = (array)$address;
        }else {
            $this->data['shipping_address_id'] = (int)$address_id;
        }
    }

    /**
     * @return array
     */
    public function getShippingAddress()
    {
        if($this->data['guest']['shipping']) {
            return (array)$this->data['guest']['shipping'];
        }elseif($this->data['guest']) {
            return (array)$this->data['guest'];
        }elseif($this->data['shipping_address_id']) {
            return $this->getAddressById((int)$this->data['shipping_address_id']);
        }
        return [];
    }

    /**
     * @param int $address_id
     * @param array $address - required if $address_id zero
     */
    public function setPaymentAddress($address_id = 0, $address = [])
    {
        if($address && $this->data['guest']){
            $this->data['guest']['payment_address'] = (array)$address;
        }else {
            $this->data['payment_address_id'] = (int)$address_id;
        }
    }

    /**
     * @return array
     */
    public function getPaymentAddress()
    {
        if($this->data['guest']['payment_address']) {
            return $this->data['guest']['payment_address'];
        }elseif($this->data['payment_address_id']) {
            return $this->getAddressById((int)$this->data['payment_address_id']);
        }
        return [];
    }

    /**
     * @param array $payment_method
     */
    public function setPaymentMethod($payment_method)
    {
        if($this->data['guest']){
            $this->data['guest']['payment_method']  = $payment_method;
        }
        $this->data['payment_method'] = $payment_method;
    }

    /**
     * @return array
     */
    public function getPayment()
    {
        if($this->data['guest']['payment_method']){
            return (array)$this->data['guest']['payment_method'];
        }else {
            return (array)$this->data['payment_method'];
        }
    }

    /**
     * @return string
     */
    public function getPaymentKey()
    {
        if($this->data['guest']['payment_method']){
            return (string)$this->data['guest']['payment_method']['id'];
        }else {
            return (string)$this->data['payment_method']['id'];
        }
    }

    /**
     * @param array $shipping_method
     */
    public function setShippingMethod( $shipping_method)
    {
        if($this->data['guest']){
            $this->data['guest']['shipping_method']  = $shipping_method;
        }
        $this->data['shipping_method'] = $shipping_method;
    }

    /**
     * @return array
     */
    public function getShipping()
    {
        return (array)$this->data['shipping_method'];
    }
    /**
     * @return array
     */
    public function getShippingKey()
    {
        return (array)$this->data['shipping_method']['id'];
    }

    /**
     * @param AOrder $order
     */
    public function setOrder(AOrder $order)
    {
        $this->order = $order;
    }

    /**
     * @return AOrder|null
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @return ACart
     */
    public function getCart()
    {
        return $this->cart;
    }
    /**
     * @param ACart $cart
     */
    public function setCart(ACart $cart)
    {
        $this->cart = $cart;
    }


    /**
     * @return ACustomer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @param ACustomer $customer
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function setCustomer(ACustomer $customer)
    {
        $this->customer = $customer;
        $c_data = [
                    'customer_group_id' => $this->customer->getCustomerGroupId(),
                    'country_id' => ($this->data['shipping_country_id'] ?: $this->data['payment_country_id']),
                    'zone_id' => ($this->data['shipping_zone_id'] ?: $this->data['payment_zone_id']),
        ];

        $this->tax = new ATax( $this->registry, $c_data );
    }

    public function getTax()
    {
        return $this->tax;
    }

    /**
     * @param array $data
     *
     * @throws LibException
     * @throws AException
     */
    public function confirmOrder($data = []){

        $order_id = (int)$data['order_id'];
        if(!$order_id){
            throw new AException(__CLASS__.': Cannot to confirm order. Unknown order id!');
        }

        $this->validatePaymentDetails($data);
        $this->processPayment($data);

        $order_status_id = Registry::config()->get($this->getPaymentKey().'_order_status_id');
        if(!$order_status_id){
            $order_status_id = Registry::order_status()->getStatusByTextId('pending');
        }

        $this->getOrder()->confirm(
            $order_id,
            $order_status_id
        );

        H::event('abc\checkout\order@confirm', [new ABaseEvent($order_id, $order_status_id)]);
    }

    /**
     * @param array $data
     *
     * @return bool
     * @throws LibException
     */
    public function validatePaymentDetails(array $data = []){
        $order_id = (int)$this->data['order_id'] ?: $data['order_id'];

        if(!$order_id){
            throw new LibException([__CLASS__.'::'.__FUNCTION__.':  Unknown order id!']);
        }

        $handler = $this->getPaymentHandler($this->getPaymentKey());
        try {
            $result = $handler->validatePaymentDetails($data);
        }catch(\Exception $e){
            throw new LibException([$e->getMessage()], $e->getCode(), $e);
        }

        if(!$result){
            throw new LibException($handler->getErrors());
        }
        $this->errors = [];
        $this->registry->get('extensions')->hk_ValidateData($this,[__FUNCTION__]);

        if($this->errors){
            throw new LibException($this->errors);
        }

        return true;
    }

    /**
     * @param array $data
     *
     * @return mixed
     * @throws LibException
     */
    public function processPayment(array $data = []){
        $handler = $this->getPaymentHandler($this->getPaymentKey());
        try {
            $result = $handler->processPayment($data);
        }catch(\Exception $e){
            throw new LibException([$e->getMessage()], $e->getCode(), $e);
        }

        if(!$result){
            throw new LibException($handler->getErrors());
        }
        try {
            H::event('abc\core\lib\checkoutBase@processPayment', [new ABaseEvent($this->data['order_id'], $data)]);
        }catch(AException $e){
            $error = new AError($e->getMessage());
            $error->toLog();
        }
        return $result;
    }

    /**
     * @param $payment_method
     *
     * @return PaymentHandlerInterface
     * @throws LibException
     */
    public function getPaymentHandler($payment_method){
        $all_modules = $this->registry->get('extensions')->getExtensionModules();
        if( !isset($all_modules[$payment_method]) || !isset($all_modules[$payment_method]['handlers']['payment']) ){
            throw new LibException([
                'Payment handler not found in '.$payment_method
                .' extension module list! '
                .'Please check modules definitions in the file extensions'.DS.$payment_method.DS.'main.php']
            );
        }

        return new $all_modules[$payment_method]['handlers']['payment']($this->registry, $this);
    }
}
