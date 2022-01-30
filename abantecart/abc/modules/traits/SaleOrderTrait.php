<?php


namespace abc\modules\traits;

use abc\core\ABC;
use abc\core\engine\Registry;
use abc\core\lib\ACustomer;
use abc\core\lib\CheckOutAdmin;
use abc\models\order\OrderTotal;

/**
 * Trait SaleOrderTrait
 * @property Registry $registry
 * @package abc\modules\traits
 */
trait SaleOrderTrait
{

    /**
     * @param $order_info
     *
     * @param array $customer_data
     *
     * @return CheckOutAdmin
     * @throws \abc\core\lib\AException
     */
    protected function initCheckout($order_info, $customer_data = [])
    {

        $checkoutData = $order_info;
        $customer_id = $order_info['customer_id'];

        $customer_data = is_array($customer_data) ? $customer_data : [];

        /**
         * @var ACustomer $aCustomer
         */
        $aCustomer = ABC::getObjectByAlias('ACustomer', [$this->registry, $customer_id] );
        $this->registry->set('customer', $aCustomer);

        //build customer data before cart loading
        $customer_data['current_store_id'] = $aCustomer->getStoreId();
        $customer_data['country_id'] = $order_info['shipping_country_id'] ?: $order_info['payment_country_id'];
        $customer_data['zone_id'] = $order_info['shipping_zone_id'] ?: $order_info['payment_zone_id'];
        $customer_data['customer_id'] = $customer_id;
        //need to include customer_group_id to calculate promotions
        $customer_data['customer_group_id'] = $aCustomer->getCustomerGroupId();
        $customer_data['tax_exempt'] = $aCustomer->isTaxExempt();

        $customer_data['cart'] =& $order_info['cart'];
        $customer_data['cart'] = (array)$customer_data['cart'];

        $balance = OrderTotal::where(['order_id' => $order_info['order_id'], 'key' => 'balance'])->first();
        if ($balance) {
            $customer_data['used_balance'] = abs($balance->value);
        }

        $checkoutData['customer_id'] = $customer_id;
        $checkoutData['customer'] = $aCustomer;
        $customer_data['coupon'] = $customer_data['coupon'] ?: $order_info['coupon'];
        $customer_data['payment_method'] = [
            'id'    => $order_info['payment_method_key'],
            'title' => $order_info['payment_method'],
        ];
        $customer_data['shipping_method'] = [
            'id'    => $order_info['shipping_method_key'],
            'title' => $order_info['shipping_method'],
        ];
        $customer_data['payment_method_key'] = $order_info['payment_method_key'];

        $c_data =& $customer_data;

        $checkoutData['cart'] = ABC::getObjectByAlias('ACart', [$this->registry, $c_data]);
        $checkout =  new CheckOutAdmin($this->registry,$checkoutData);
        $checkoutData['cart']->conciergeMode = $checkout->getConciergeMode();

        //put them into registry for access from extensions models
        $this->registry->set('cart', $checkout->getCart());
        $this->registry->set('tax', $checkout->getTax());
        $this->registry->set('customer', $checkout->getCustomer());

        return $checkout;
    }

}