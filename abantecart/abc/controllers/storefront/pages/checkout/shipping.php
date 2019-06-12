<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2017 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/

namespace abc\controllers\storefront;

use abc\core\engine\AController;
use abc\core\engine\AForm;
use abc\core\lib\CheckOut;
use abc\models\customer\Address;

/**
 * Class ControllerPagesCheckoutShipping
 *
 * @package abc\controllers\storefront
 *
 * @property Checkout $checkout
 */
class ControllerPagesCheckoutShipping extends AController
{
    public $error = [];
    public $data = [];

    public function main()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $cart_rt = 'checkout/cart';
        $checkout_rt = 'checkout/shipping';
        $payment_rt = 'checkout/payment';
        $login_rt = 'account/login';
        $address_rt = 'checkout/address/shipping';
        if ($this->config->get('embed_mode') == true) {
            $cart_rt = 'r/checkout/cart/embed';
        }

        //validate if order min/max are met
        if (!$this->cart->hasMinRequirement() || !$this->cart->hasMaxRequirement()) {
            abc_redirect($this->html->getSecureURL('checkout/cart'));
        }

        if ($this->request->is_POST() && $this->validate()) {

            $shipping = explode('.', $this->request->post['shipping_method']);
            $this->session->data['shipping_method'] =
                $this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]];
            $this->checkout->setShippingMethod($this->session->data['shipping_method']);
            $this->session->data['comment'] = strip_tags($this->request->post['comment']);

            //process data
            $this->extensions->hk_ProcessData($this);

            abc_redirect($this->html->getSecureURL($payment_rt));
        }

        if (!$this->cart->hasProducts() || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            abc_redirect($this->html->getSecureURL($cart_rt));
        }

        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->html->getSecureURL($checkout_rt);
            abc_redirect($this->html->getSecureURL($login_rt));
        }
        unset($this->session->data['redirect']);

        //if no products require shipping go to payment step
        if (!$this->cart->hasShipping()) {
            unset($this->session->data['shipping_address_id']);
            unset($this->session->data['shipping_method']);
            unset($this->session->data['shipping_methods']);

            $this->tax->setZone($this->session->data['country_id'], $this->session->data['zone_id']);
            abc_redirect($this->html->getSecureURL($payment_rt, "&back=cart"));
        }

        //If no shipping address is set yet, use default
        if (!isset($this->session->data['shipping_address_id'])) {
            $this->session->data['shipping_address_id'] = $this->customer->getAddressId();

        }

        //still missing address, go to address selection page
        if (!$this->session->data['shipping_address_id']) {
            abc_redirect($this->html->getSecureURL($address_rt));
        }
        $this->checkout->setShippingAddress($this->session->data['shipping_address_id']);
        $shipping_address = [];
        if($address = Address::find($this->session->data['shipping_address_id'])){
            $shipping_address = $address->toArray();
        }
        //something wrong with shipping address go to address selection page
        if (!$shipping_address) {
            abc_redirect($this->html->getSecureURL($address_rt));
        }

        // if tax zone is taken from shipping address
        if (!$this->config->get('config_tax_customer')) {
            $this->tax->setZone($shipping_address['country_id'], $shipping_address['zone_id']);
        } else { // if tax zone is taken from billing address
            $address = Address::find($this->customer->getAddressId());
            $this->tax->setZone($address->country_id, $address->zone_id);
        }

        if (!isset($this->session->data['shipping_methods']) || !$this->config->get('config_shipping_session')) {
            $shipping_methods = $this->checkout->getShippingList();
            $this->session->data['shipping_methods'] = $shipping_methods;
        }

        //# If only 1 shipping and it is set to be defaulted, select and skip and redirect to payment
        if (count((array)$this->session->data['shipping_methods']) == 1 && $this->request->get['mode'] != 'edit') {
            //set only method
            $only_method = (array)$this->session->data['shipping_methods'];
            foreach ($only_method as $key => $value) {
                #Check config if we allowed to set this shipping and skip the step
                $autoselect = $only_method[$key]['settings'][$key."_autoselect"];
                if ($autoselect) {
                    if (sizeof($only_method[$key]['quote']) == 1) {
                        $this->session->data['shipping_method'] = current($only_method[$key]['quote']);
                        abc_redirect($this->html->getSecureURL($payment_rt, "&back=cart"));
                    }
                }
            }
        }

        $this->document->setTitle($this->language->get('heading_title'));
        $this->document->resetBreadcrumbs();

        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getHomeURL(),
                'text'      => $this->language->get('text_home'),
                'separator' => false,
            ]);

        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSecureURL($cart_rt),
                'text'      => $this->language->get('text_basket'),
                'separator' => $this->language->get('text_separator'),
            ]);

        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSecureURL($checkout_rt),
                'text'      => $this->language->get('text_shipping'),
                'separator' => $this->language->get('text_separator'),
            ]);

        $this->data['error_warning'] = $this->error['warning'];

        if (isset($this->session->data['shipping_methods']) && !$this->session->data['shipping_methods']) {
            $this->data['error_warning'] = $this->language->get('error_no_shipping');
        }

        $this->data['address'] = $this->customer->getFormattedAddress(
            $shipping_address,
            $shipping_address['address_format']
        );

        $item = $this->html->buildElement(
            [
                'type'  => 'button',
                'name'  => 'change_address',
                'style' => 'button',
                'text'  => $this->language->get('button_change_address'),
            ]);
        $this->data['change_address'] = $item;
        $this->data['change_address_href'] = $this->html->getSecureURL($address_rt);

        $form = new AForm();
        $form->setForm(['form_name' => 'shipping']);
        $this->data['form']['form_open'] = $form->getFieldHtml(
            [
                'type'   => 'form',
                'name'   => 'shipping',
                'action' => $this->html->getSecureURL($checkout_rt),
                'csrf'   => true,
            ]
        );

        $this->data['shipping_methods'] = $this->session->data['shipping_methods']
            ? $this->session->data['shipping_methods']
            : [];

        $shipping = $this->session->data['shipping_method']['id'];
        if ($this->data['shipping_methods']) {
            foreach ($this->data['shipping_methods'] as $k => $v) {
                if ($v['quote'] && is_array($v['quote'])) {
                    foreach ($v['quote'] as $key => $val) {
                        //check if we have only one method and select by default if was selected before
                        $selected = false;
                        if (count($this->data['shipping_methods']) == 1 && count($v['quote']) == 1) {
                            $selected = true;
                        } else {
                            if ($shipping == $val['id']) {
                                $selected = true;
                            }
                        }

                        $this->data['shipping_methods'][$k]['quote'][$key]['radio'] = $form->getFieldHtml(
                            [
                                'type'    => 'radio',
                                'id'      => $val['id'],
                                'name'    => 'shipping_method',
                                'options' => [$val['id'] => ''],
                                'value'   => $selected,
                            ]);
                    }
                }
            }
        } else {
            $this->data['shipping_methods'] = [];
        }

        $this->data['comment'] = isset($this->request->post['comment'])
            ? $this->request->post['comment']
            : $this->session->data['comment'];
        $this->data['form']['comment'] = $form->getFieldHtml(
            [
                'type'  => 'textarea',
                'name'  => 'comment',
                'value' => $this->data['comment'],
                'attr'  => ' rows="3" style="width: 99%" ',
            ]);
        $this->data['back'] = $this->html->getSecureURL($cart_rt);
        $this->data['form']['back'] = $form->getFieldHtml(
            [
                'type'  => 'button',
                'name'  => 'back',
                'style' => 'button',
                'text'  => $this->language->get('button_back'),
            ]);
        $this->data['form']['continue'] = $form->getFieldHtml(
            [
                'type' => 'submit',
                'name' => $this->language->get('button_continue'),
            ]);
        //render buttons
        $this->view->batchAssign($this->data);
        if ($this->config->get('embed_mode') == true) {
            $this->view->assign('buttons', $this->view->fetch('embed/checkout/shipping.buttons.tpl'));
            //load special headers
            $this->addChild('responses/embed/head', 'head');
            $this->addChild('responses/embed/footer', 'footer');
            $this->processTemplate('embed/checkout/shipping.tpl');
        } else {
            $this->view->assign('buttons', $this->view->fetch('pages/checkout/shipping.buttons.tpl'));
            $this->processTemplate('pages/checkout/shipping.tpl');
        }

        //update data before render
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

    }

    public function validate()
    {
        if (!$this->csrftoken->isTokenValid()) {
            $this->error['warning'] = $this->language->get('error_unknown');
        } else {
            if (!isset($this->request->post['shipping_method'])) {
                $this->error['warning'] = $this->language->get('error_shipping');
            } else {
                $shipping = explode('.', $this->request->post['shipping_method']);
                if (!isset($this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]])) {
                    $this->error['warning'] = $this->language->get('error_shipping');
                }
            }
        }

        //validate post data
        $this->extensions->hk_ValidateData($this);

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }
}
