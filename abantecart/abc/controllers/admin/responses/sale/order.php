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

namespace abc\controllers\admin;

use abc\core\ABC;
use abc\core\engine\AController;
use abc\core\lib\AError;
use abc\core\lib\AJson;
use abc\core\lib\APromotion;
use abc\core\lib\CheckoutBase;
use abc\models\order\Order;
use abc\modules\traits\SaleOrderTrait;
use H;

class ControllerResponsesSaleOrder extends AController
{
    use SaleOrderTrait;
    /**
     * @var CheckoutBase
     */
    public $checkout;

    public function main()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $output = [];
        if (!$this->user->canModify('sale/order')) {
            $error = new AError('');
            return $error->toJSONResponse('NO_PERMISSIONS_403',
                [
                    'error_text'  => sprintf($this->language->get('error_permission_modify'), 'sale/order'),
                    'reset_value' => true,
                ]);
        }

        $this->loadLanguage('sale/order');
        $data = $this->session->data['admin_order'];
        if ($this->request->post['shipping_method']) {
            $data['shipping_method_key'] = $this->request->post['shipping_method_key'];
            $data['shipping_method'] = $this->request->post['shipping_method'];
        }
        if ($this->request->post['payment_method']) {
            $data['payment_method_key'] = $this->request->post['payment_method'];
        }
        $this->checkout = $this->initCheckout($data);

        switch($this->request->get['action']){
            case 'get_shippings':
                $output = $this->getShippings();
                break;
            case 'get_payments':
                $output = $this->getPayments();
                break;
            case 'recalc_totals':
                $output = $this->getTotals();
                break;
            case 'apply_coupon':
                $output = $this->applyCoupon();
                if($output === false){
                    $error = new AError('');
                    return $error->toJSONResponse('VALIDATION_ERROR_406',
                        [
                            'error_text'  => $this->language->get('error_coupon'),
                            'reset_value' => true,
                        ]);
                }
                break;

        }
        $this->data['output'] = $output;
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->load->library('json');
        $this->response->setOutput(AJson::encode($this->data['output']));
    }

    protected function getTotals()
    {
        $output = [];
        $display_totals = $this->checkout->getCart()->buildTotalDisplay(true);

        $output['totals'] = $display_totals['total_data'];
        return $output;
    }

    protected function getShippings()
    {
        $output = ['title' => $this->language->get('entry_shipping_method'), 'html' => ''];

        //skip shipping processing if not required.
        if ($this->checkout->getCart()->hasShipping()) {
            $shipping_methods = $this->checkout->getShippingList();
            //add ready selectbox element
            if ($shipping_methods) {
                $options = [];
                foreach ($shipping_methods as $shp_data) {
                    $shp_data['quote'] = (array)$shp_data['quote'];
                    foreach ($shp_data['quote'] as $qt_data) {
                        $options[$qt_data['id']] = $qt_data['title']." - ".$qt_data['text'];
                    }
                }
                if ($options) {
                    $selected_method = $this->checkout->getShipping();
                    $output['html'] = $this->html->buildElement(
                        [
                        'type'    => 'selectbox',
                        'name'    => 'shipping_method',
                        'options' => $options,
                        'value'   => $selected_method['id'],
                        'style'   => 'large-field',
                        ]
                    )->getHTML();
                }
            }
        }else{
            $output['html'] = 'no shipping required';
        }
        return $output;
    }

    protected function getPayments()
    {
        $output = ['title' => $this->language->get('entry_payment_method'), 'html' => ''];

        $payment_methods = $this->checkout->getPaymentList();
        $options = array_column($payment_methods, 'title', 'id');

        //skip shipping processing if not required.
        if ($options) {
            $output['html'] = $this->html->buildElement(
                    [
                    'type'    => 'selectbox',
                    'name'    => 'payment_method',
                    'options' => $options,
                    'value'   => $this->checkout->getPayment(),
                    'style'   => 'large-field',
                    ]
            )->getHTML();
        }elseif($this->checkout->getCart()->getProducts()){
            $output['html'] = $this->data['error_warning'] = sprintf(
                                        $this->language->get('error_no_payments'),
                                        $this->html->getSecureURL('extension/extensions/payments')
            );
            $output['error'] = true;
        }
        return $output;
    }

    protected function applyCoupon()
    {
        if($this->request->post['coupon']){
            /**
             * @var APromotion $promotion
             */
            $promotion = ABC::getObjectByAlias('APromotion');
            $coupon = $promotion->getCouponData($this->request->post['coupon']);

            if (!$coupon) {
                return false;
            }
        }
        $this->session->data['admin_order']['coupon'] = $this->request->post['coupon'];
        return ['result' => true ];
    }

    public function validateCoupon()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->reInitOrder($this->request->get['order_id']);
        $promotion = new APromotion($this->checkout->getCustomer(), $this->checkout->getCart());
        $coupon = $promotion->getCouponData($this->request->get['coupon_code']);

        if (!$coupon) {
            $error = new AError('');
            return $error->toJSONResponse('NO_PERMISSIONS_403',
                [
                    'error_text'  => $this->language->get('error_coupon'),
                    'reset_value' => true,
                ]);
        }
        $this->data['output'] = $coupon;
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->load->library('json');
        $this->response->setOutput(AJson::encode($this->data['output']));
    }

    public function recalculateExistingOrderTotals()
    {
        $order_id = (int)$this->request->get['order_id'];

        if (!$order_id) {
            return null;
        }
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        /*if (!$this->user->canModify('sale/order')) {
            $error = new AError('');
            return $error->toJSONResponse('NO_PERMISSIONS_403',
                [
                    'error_text'  => sprintf($this->language->get('error_permission_modify'), 'sale/order'),
                    'reset_value' => true,
                ]);
        }*/

        $customer_data = (array)$this->data['customer_data'];
        if ($this->request->post['manual_totals']) {
            foreach ($this->request->post['manual_totals'] as $total_txt_id => $value) {
                if ($total_txt_id == 'coupon') {
                    $customer_data['coupon'] = $value['coupon_code'];
                }
            }
        }
        $this->reInitOrder($order_id, $customer_data);


        $output = $this->getTotals();

        $this->data['output'] = $output;
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->load->library('json');
        $this->response->setOutput(AJson::encode($this->data['output']));
    }

    protected function reInitOrder($order_id, $customer_data = [])
    {
        $this->loadLanguage('sale/order');

        /**
         * @var Order $order_info
         */
        $order_info = Order::find($order_id);
        if (!$order_info) {
            return null;
        }

        $orderData = $order_info->toArray();
        //$guest = !($orderData['customer_id'] > 0);

        //initialize existing order as new
        $this->checkout = $this->initCheckout($orderData, $customer_data);
        $cart = $this->checkout->getCart();
        foreach ($this->request->post['product'] as $order_product_id => $order_product) {
            $cart->add(
                            $order_product['product_id'],
                            $order_product['quantity'],
                            $order_product['option'],
                            H::preformatFloat($order_product['price']),
                            $order_product['order_product_id']
                        );
        }
    }

    /*
     * Calculate product total based on options selected
     * */
    public function calculateTotal()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->checkout = $this->initCheckout(['customer_id' => $this->request->get['customer_id']]);
        $output = [];

        if (H::has_value($this->request->post['product_id'])
            && is_numeric($this->request->post['product_id'])) {
            $product_id = $this->request->post['product_id'];
            if (isset($this->request->post['option'])) {
                $option = $this->request->post['option'];
            } else {
                $option = [];
            }

            if (isset($this->request->post['quantity'])) {
                $quantity = (int)$this->request->post['quantity'];
            } else {
                $quantity = 1;
            }

            $custom_price = $this->request->post['price'] ?: null;
            $result = $this->checkout->getCart()->buildProductDetails($product_id, $quantity, $option, $custom_price);

            $output['total'] = (float)$this->tax->calculate(
                $result['total'],
                $result['tax_class_id'],
                (int)$this->config->get('config_tax')
            );
            $output['price'] = (float)$this->tax->calculate(
                $result['price'],
                $result['tax_class_id'],
                (int)$this->config->get('config_tax')
            );

            $output['total'] = $this->currency->format_total($output['price'], $quantity);
            $output['price'] = $this->currency->format($output['price']);

        }

        $this->data['output'] = $output;
        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->load->library('json');
        $this->response->setOutput(AJson::encode($this->data['output']));
    }

}