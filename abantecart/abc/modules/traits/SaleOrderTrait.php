<?php


namespace abc\modules\traits;

use abc\core\ABC;
use abc\core\engine\Registry;
use abc\core\lib\ACustomer;
use abc\core\lib\CheckOutAdmin;

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
     * @return CheckOutAdmin
     * @throws \abc\core\lib\AException
     */
    protected function initCheckout($order_info)
    {

        $checkoutData = $order_info;
        $customer_id = $order_info['customer_id'];

        $customer_data = [];
        /**
         * @var ACustomer $aCustomer
         */
        $aCustomer = ABC::getObjectByAlias('ACustomer', [$this->registry, $customer_id] );
        $this->registry->set('customer', $aCustomer);

        //build customer data before cart loading
        $customer_data['current_store_id'] = $aCustomer->getStoreId();
        $customer_data['country_id'] = $order_info['shipping_country_id'];
        $customer_data['zone_id'] = $order_info['shipping_zone_id'];
        $customer_data['customer_id'] = $customer_id;
        //need to include customer_group_id to calculate promotions
        $customer_data['customer_group_id'] = $aCustomer->getCustomerGroupId();
        $customer_data['tax_exempt'] = $aCustomer->isTaxExempt();

        $customer_data['cart'] =& $order_info['cart'];
        $customer_data['cart'] = (array)$customer_data['cart'];

        $checkoutData['customer'] = $aCustomer;
        $customer_data['coupon'] = $order_info['coupon'];

        $checkoutData['cart'] = ABC::getObjectByAlias('ACart', [$this->registry, $customer_data]);

        $checkout =  new CheckOutAdmin($this->registry,$checkoutData);

        //put them into registry for access from extensions models
        $this->registry->set('cart', $checkout->getCart());
        $this->registry->set('tax', $checkout->getTax());
        $this->registry->set('customer', $checkout->getCustomer());

        return $checkout;
    }

}