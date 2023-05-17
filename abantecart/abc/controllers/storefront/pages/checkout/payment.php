<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2022 Belavier Commerce LLC

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
use abc\core\lib\APromotion;
use abc\core\engine\HtmlElementFactory;
use abc\core\lib\CheckOut;
use abc\models\content\Content;
use abc\models\customer\Address;

/**
 * Class ControllerPagesCheckoutPayment
 *
 * @package abc\controllers\storefront
 * @property  Checkout $checkout
 */
class ControllerPagesCheckoutPayment extends AController
{
    public $error = [];

    public function main()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $cart_rt = 'checkout/cart';
        $checkout_rt = 'checkout/shipping';
        $payment_rt = 'checkout/payment';
        $login_rt = 'account/login';
        $address_rt = 'checkout/address/payment';
        $confirm_rt = 'checkout/confirm';
        if ($this->config->get('embed_mode')) {
            $cart_rt = 'r/checkout/cart/embed';
        }

        //validate if order min/max are met
        if (!$this->cart->hasMinRequirement() || !$this->cart->hasMaxRequirement()) {
            abc_redirect($this->html->getSecureURL($cart_rt));
        }

        //Selections are posted, validate and apply
        if ($this->request->is_POST() && isset($this->request->post['coupon'])) {
            if (!$this->csrftoken->isTokenValid()) {
                $this->error['warning'] = $this->language->get('error_unknown');
            } else {
                //reload and re-apply balance if was requested
                $param = '';
                if (isset($this->session->data['used_balance'])) {
                    $param = '&balance=reapply';
                }

                if (isset($this->request->post['reset_coupon'])) {
                    //remove coupon
                    unset($this->session->data['coupon']);
                    $this->session->data['success'] = $this->language->get('text_coupon_removal');

                    //process data
                    $this->extensions->hk_ProcessData($this, 'reset_coupon');
                    abc_redirect($this->html->getSecureURL($payment_rt, $param));
                } else {
                    if ($this->validateCoupon()) {
                        $this->session->data['coupon'] = $this->request->post['coupon'];
                        $this->session->data['success'] = $this->language->get('text_success');

                        //process data
                        $this->extensions->hk_ProcessData($this, 'apply_coupon');
                        abc_redirect($this->html->getSecureURL($payment_rt, $param));
                    }
                }
            }
        }

        if (isset($this->request->get['balance'])) {
            //process balance
            $this->processBalance($this->request->get['balance']);
            unset($this->request->get['balance']);
        }
        //we might have some uncleaned session. Show only if comes together with used balance
        if ($this->session->data['used_balance']) {
            $this->data['used_balance_full'] = $this->session->data['used_balance_full'];
        }

        $order_totals = $this->cart->buildTotalDisplay(true);
        $order_total = $order_totals['total'];

        if (!$this->cart->hasProducts() || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            abc_redirect($this->html->getSecureURL($cart_rt));
        }

        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->html->getSecureURL($checkout_rt);
            abc_redirect($this->html->getSecureURL($login_rt));
        }

        if ($this->cart->hasShipping()) {
            if (!isset($this->session->data['shipping_address_id']) || !$this->session->data['shipping_address_id']) {
                abc_redirect($this->html->getSecureURL($checkout_rt));
            }

            if (!isset($this->session->data['shipping_method'])) {
                abc_redirect($this->html->getSecureURL($checkout_rt));
            }
        } else {
            unset($this->session->data['shipping_address_id']);
            unset($this->session->data['shipping_method']);
            unset($this->session->data['shipping_methods']);

            //$this->tax->setZone($this->config->get('config_country_id'), $this->config->get('config_zone_id'));
            $this->tax->setZone($this->session->data['country_id'], $this->session->data['zone_id']);
        }

        if (!isset($this->session->data['payment_address_id']) && isset($this->session->data['shipping_address_id'])
            && $this->session->data['shipping_address_id']) {
            $this->session->data['payment_address_id'] = $this->session->data['shipping_address_id'];
        }

        if (!isset($this->session->data['payment_address_id'])) {
            $this->session->data['payment_address_id'] = $this->customer->getAddressId();
        }

        if (!$this->session->data['payment_address_id']) {
            abc_redirect($this->html->getSecureURL($address_rt));
        }
        $this->checkout->setPaymentAddress($this->session->data['payment_address_id']);

        $payment_address = [];
        $address = Address::getAddresses(
            $this->customer->getId(),
            $this->language->getLanguageID(),
            $this->session->data['payment_address_id']
        );
        if ($address) {
            $payment_address = $address->toArray();
        }

        if (!$payment_address) {
            abc_redirect($this->html->getSecureURL($address_rt));
        }

        if (!$this->cart->hasShipping() || $this->config->get('config_tax_customer')) {
            $this->tax->setZone($payment_address['country_id'], $payment_address['zone_id']);
        }

        $payment_methods = $this->checkout->getPaymentList();

        $this->session->data['payment_methods'] = $payment_methods;

        if ($this->request->is_POST() && !isset($this->request->post['coupon']) && $this->validate()) {
            $this->session->data['payment_method'] =
                $this->session->data['payment_methods'][$this->request->post['payment_method']];

            $this->session->data['comment'] = strip_tags($this->request->post['comment']);

            $this->extensions->hk_ProcessData($this, 'confirm');
            abc_redirect($this->html->getSecureURL($confirm_rt));
        }

        if ($this->cart->getTotalAmount() == 0 && $this->request->get['mode'] != 'edit') {
            $this->session->data['payment_method'] = [
                'id'    => 'no_payment_required',
                'title' => $this->language->get('no_payment_required'),
            ];

            abc_redirect($this->html->getSecureURL($confirm_rt));

        }

        //# If only 1 payment and it is set to be defaulted, select and skip and redirect to confirmation
        if (count($this->session->data['payment_methods']) == 1 && $this->request->get['mode'] != 'edit') {
            //set only method
            $only_method = $this->session->data['payment_methods'];
            reset($only_method);
            $pkey = key($only_method);
            if ($only_method[$pkey]['settings'][$pkey . "_autoselect"]) {
                $this->session->data['payment_method'] = $only_method[$pkey];
                abc_redirect($this->html->getSecureURL($confirm_rt));
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
                'text'      => $this->language->get('entry_shipping'),
                'separator' => $this->language->get('text_separator'),
            ]);

        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSecureURL($payment_rt),
                'text'      => $this->language->get('entry_payment'),
                'separator' => $this->language->get('text_separator'),
            ]);

        if (isset($this->session->data['error'])) {
            $this->view->assign('error_warning', $this->session->data['error']);
            unset($this->session->data['error']);
        } else {
            $this->view->assign('error_warning', $this->error['warning']);
        }

        $this->view->assign('success', $this->session->data['success']);
        if (isset($this->session->data['success'])) {
            unset($this->session->data['success']);
        }
        $action = $this->html->getSecureURL($payment_rt, '&mode=' . $this->request->get['mode'], true);

        $this->data['change_address'] = HtmlElementFactory::create([
            'type'  => 'button',
            'name'  => 'change_address',
            'style' => 'button',
            'text'  => $this->language->get('button_change_address'),
        ]);

        $this->data['change_address_href'] = $this->html->getSecureURL($address_rt);

        if ($this->config->get('coupon_status')) {
            $this->view->assign('coupon_status', $this->config->get('coupon_status'));
            $coupon_form = $this->dispatch('blocks/coupon_codes', ['action' => $action]);
            $this->view->assign('coupon_form', $coupon_form->dispatchGetOutput());
            unset($coupon_form);
        }

        $this->data['address'] = $this->customer->getFormattedAddress(
            $payment_address,
            $payment_address['address_format']
        );

        $form = new AForm();
        $form->setForm(['form_name' => 'payment']);
        $this->data['form']['form_open'] = $form->getFieldHtml(
            [
                'type'   => 'form',
                'name'   => 'payment',
                'action' => $action,
                'csrf'   => true,
            ]
        );

        $this->data['payment_methods'] = $this->session->data['payment_methods'];
        $payment = $this->request->post['payment_method'] ?? $this->session->data['payment_method']['id'];

        //balance handling
        $balance_def_currency = $this->customer->getBalance();
        //is balance enough to cover all order amount
        $this->data['balance_enough'] = $balance_def_currency >= $order_total;

        $balance = $this->currency->convert(
            $balance_def_currency,
            $this->config->get('config_currency'),
            $this->session->data['currency']
        );

        if ($balance != 0 || ($balance == 0 && $this->config->get('config_zero_customer_balance'))
            && (float)$this->session->data['used_balance'] != 0) {
            if ((float)$this->session->data['used_balance'] == 0 && $balance > 0) {
                $this->data['apply_balance_button'] = $this->html->buildElement(
                    [
                        'type'  => 'button',
                        'name'  => 'apply_balance',
                        'href'  => $this->html->getSecureURL($payment_rt, '&mode=edit&balance=apply', true),
                        'text'  => $this->language->get('button_pay_with_balance'),
                        'icon'  => 'fa fa-money',
                        'style' => 'btn-default',
                    ]
                );
            } elseif ((float)$this->session->data['used_balance'] > 0) {
                $this->data['apply_balance_button'] = $this->html->buildElement(
                    [
                        'type'  => 'button',
                        'name'  => 'apply_balance',
                        'href'  => $this->html->getSecureURL($payment_rt, '&mode=edit&balance=disapply', true),
                        'text'  => $this->language->get('button_disapply_balance'),
                        'icon'  => 'fa fa-times',
                        'style' => 'btn btn-default',
                    ]
                );
                //if balance cover all order amount - build button for continue checkout
                if ($this->session->data['used_balance_full']) {
                    $this->data['balance_continue_button'] = $this->html->buildElement(
                        [
                            'type' => 'submit',
                            'name' => $this->language->get('button_continue'),
                            'icon' => 'fa fa-arrow-right',
                        ]
                    );
                }
            }

            $this->data['text_balance'] = $this->language->get('text_balance_checkout');
            $this->data['balance_remains'] =
            $this->data['balance_value'] = $this->currency->format($balance, $this->session->data['currency'], 1);

            if ((float)$this->session->data['used_balance'] > 0) {
                $this->data['balance_remains'] = $this->currency->format(
                    $balance_def_currency - (float)$this->session->data['used_balance']
                );
                $this->data['balance_used'] = $this->currency->format((float)$this->session->data['used_balance']);
                $this->data['text_applied_balance'] = $this->language->get('text_applied_balance');
            }
        }

        if ($this->data['payment_methods']) {
            foreach ($this->data['payment_methods'] as $k => $v) {
                //check if we have only one method and select by default if was selected before
                $selected = false;
                $autoSelect = $v['settings'][$k . "_autoselect"];
                if (count($this->data['payment_methods']) == 1) {
                    $selected = true;
                } else {
                    if ($payment == $v['id']) {
                        $selected = true;
                    } else {
                        if ($autoSelect) {
                            $selected = true;
                        }
                    }
                }

                $this->data['payment_methods'][$k]['radio'] = $form->getFieldHtml(
                    [
                        'type'    => 'radio',
                        'name'    => 'payment_method',
                        'options' => [$v['id'] => ''],
                        'value'   => $selected,
                    ]);
            }
        } else {
            $this->data['payment_methods'] = [];
        }

        $this->data['comment'] = $this->request->post['comment'] ?? $this->session->data['comment'];
        $this->data['form']['comment'] = $form->getFieldHtml(
            [
                'type'  => 'textarea',
                'name'  => 'comment',
                'value' => $this->data['comment'],
                'attr'  => ' rows="3" style="width: 99%" ',
            ]);

        if ($this->config->get('config_checkout_id')) {
            $content_info = Content::getContent((int)$this->config->get('config_checkout_id'))?->toArray();
            if ($content_info) {
                $this->data['text_agree'] = $this->language->get('text_agree');
                $this->data['text_agree_href'] = $this->html->getURL(
                    'r/content/content/loadInfo',
                    '&content_id=' . $this->config->get('config_checkout_id'),
                    true
                );
                $this->data['text_agree_href_text'] = $content_info['title'];
            } else {
                $this->data['text_agree'] = '';
            }
        } else {
            $this->data['text_agree'] = '';
        }

        if ($this->data['text_agree']) {
            $this->data['form']['agree'] = $form->getFieldHtml([
                'type'    => 'checkbox',
                'name'    => 'agree',
                'value'   => '1',
                'checked' => (bool)$this->request->post['agree'],
            ]);
        }

        $this->data['agree'] = $this->request->post['agree'];
        //check return URL. If no or only one shipping go back to cart page
        if ($this->request->get['back'] == 'cart' || !$this->cart->hasShipping()) {
            $this->data['back'] = $this->html->getSecureURL($cart_rt);
        } else {
            $this->data['back'] = $this->html->getSecureURL($checkout_rt);
        }

        $this->data['form']['back'] = $form->getFieldHtml(
            [
                'type'  => 'button',
                'name'  => 'back',
                'style' => 'button',
                'text'  => $this->language->get('button_back'),
            ]
        );
        $this->data['form']['continue'] = $form->getFieldHtml(
            [
                'type' => 'submit',
                'name' => $this->language->get('button_continue'),
            ]
        );

        //render buttons
        $this->view->batchAssign($this->data);
        if ($this->config->get('embed_mode')) {
            $this->view->assign('buttons', $this->view->fetch('embed/checkout/payment.buttons.tpl'));
            //load special headers
            $this->addChild('responses/embed/head', 'head');
            $this->addChild('responses/embed/footer', 'footer');
            $this->processTemplate('embed/checkout/payment.tpl');
        } else {
            $this->view->assign('buttons', $this->view->fetch('pages/checkout/payment.buttons.tpl'));
            $this->processTemplate('pages/checkout/payment.tpl');
        }

        //update data before render
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

    }

    protected function processBalance($action)
    {
        if ($action == 'disapply' || $action == 'reapply') {
            unset($this->session->data['used_balance'],
                $this->request->get['balance'],
                $this->session->data['used_balance_full']
            );
        }
        if ($action == 'apply' || $action == 'reapply') {
            $balance = $this->customer->getBalance();
            $order_totals = $this->cart->buildTotalDisplay(true);
            $order_total = $order_totals['total'];
            if ($this->session->data['used_balance']) {
                #check if we still have balance.
                if ($this->session->data['used_balance'] <= $balance) {
                    $this->session->data['used_balance_full'] = true;
                } else {
                    //if balance become less or 0 reapply partial
                    $this->session->data['used_balance'] = $balance;
                    $this->session->data['used_balance_full'] = false;
                }
            } else {
                if ($balance > 0) {
                    if ($balance >= $order_total) {
                        $this->session->data['used_balance'] = $order_total;
                        $this->session->data['used_balance_full'] = true;

                    } else { //partial pay
                        $this->session->data['used_balance'] = $balance;
                        $this->session->data['used_balance_full'] = false;
                    }
                }
            }
            //if balance enough to cover order amount
            if ($this->cart->getTotalAmount() == 0 && $this->session->data['used_balance_full']) {
                $this->session->data['payment_method'] = [
                    'id'    => 'no_payment_required',
                    'title' => $this->language->get('no_payment_required'),
                ];
                //if enough -redirect on confirmation page
                abc_redirect($this->html->getSecureURL('checkout/confirm'));
            }
        }
    }

    protected function validate()
    {
        if ($this->cart->getFinalTotal()) {
            if (!isset($this->request->post['payment_method'])) {
                $this->error['warning'] = $this->language->get('error_payment');
                return false;
            } else {
                if (!isset($this->session->data['payment_methods'][$this->request->post['payment_method']])) {
                    $this->error['warning'] = $this->language->get('error_payment');
                    return false;
                }
            }
        }

        if ($this->config->get('config_checkout_id')) {
            $content_info = Content::getContent((int)$this->config->get('config_checkout_id'))?->toArray();
            if ($content_info) {
                if (!isset($this->request->post['agree'])) {
                    $this->error['warning'] = sprintf($this->language->get('error_agree'), $content_info['title']);
                    return false;
                }
            }
        }

        //validate post data
        $this->extensions->hk_ValidateData($this, __FUNCTION__);

        return (!$this->error);
    }

    protected function validateCoupon()
    {
        $promotion = new APromotion();
        $coupon = $promotion->getCouponData($this->request->post['coupon']);
        if (!$coupon) {
            $this->error['warning'] = $this->language->get('error_coupon');
        }

        //validate post data
        $this->extensions->hk_ValidateData($this, __FUNCTION__);

        return (!$this->error);
    }
}