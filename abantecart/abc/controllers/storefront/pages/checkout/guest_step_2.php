<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2023 Belavier Commerce LLC

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
use abc\core\lib\CheckOut;
use abc\models\content\Content;

/**
 * Class ControllerPagesCheckoutGuestStep2
 *
 * @package abc\controllers\storefront
 * @property Checkout $checkout
 */
class ControllerPagesCheckoutGuestStep2 extends AController
{
    public $error = [];

    public function main()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        //is this an embed mode
        $cart_rt = 'checkout/cart';
        if ($this->config->get('embed_mode')) {
            $cart_rt = 'r/checkout/cart/embed';
        }

        if (!$this->cart->hasProducts()
            || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))
        ) {
            abc_redirect($this->html->getSecureURL($cart_rt));
        }

        //validate if order min/max are met
        if (!$this->cart->hasMinRequirement() || !$this->cart->hasMaxRequirement()) {
            abc_redirect($this->html->getSecureURL($cart_rt));
        }

        if ($this->customer->isLogged()) {
            abc_redirect($this->html->getSecureURL('checkout/shipping'));
        }

        if (!$this->config->get('config_guest_checkout') || $this->cart->hasDownload()) {
            $this->session->data['redirect'] = $this->html->getSecureURL('checkout/shipping');
            abc_redirect($this->html->getSecureURL('account/login'));
        }

        if (!isset($this->session->data['guest'])) {
            abc_redirect($this->html->getSecureURL('checkout/guest_step_1'));
        }

        if (!$this->cart->hasShipping()) {
            unset($this->session->data['shipping_method'], $this->session->data['shipping_methods']);
            $this->checkout->setShippingMethod(null);
            $this->tax->setZone($this->session->data['country_id'], $this->session->data['zone_id']);
        }

        $this->document->setTitle($this->language->get('heading_title'));

        if ($this->request->is_POST()) {

            if (!$this->csrftoken->isTokenValid()) {
                $this->error['warning'] = $this->language->get('error_unknown');
            } else {
                if (isset($this->request->post['coupon'])) {
                    if (isset($this->request->post['reset_coupon'])) {
                        //remove coupon
                        unset($this->session->data['coupon']);
                        $this->session->data['success'] = $this->language->get('text_coupon_removal');

                        //process data
                        $this->extensions->hk_ProcessData($this, 'reset_coupon');
                        abc_redirect($this->html->getSecureURL('checkout/guest_step_3'));
                    } else {
                        if ($this->validateCoupon($this->request->post)) {
                            $this->session->data['coupon'] = $this->request->post['coupon'];
                            $this->session->data['success'] = $this->language->get('text_success');

                            if ($this->cart->getFinalTotal() == 0 && $this->request->get['mode'] != 'edit') {
                                $this->session->data['payment_method'] = [
                                    'id'    => 'no_payment_required',
                                    'title' => $this->language->get('no_payment_required'),
                                ];
                            }
                            //process data
                            $this->extensions->hk_ProcessData($this, 'apply_coupon');
                            abc_redirect($this->html->getSecureURL('checkout/guest_step_3'));
                        }
                    }
                }

                if (!isset($this->request->post['coupon']) && $this->validate($this->request->post)) {
                    if (isset($this->request->post['shipping_method'])) {
                        $shipping = explode('.', $this->request->post['shipping_method']);
                        $this->session->data['shipping_method'] =
                            $this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]];
                    }
                    if ($this->cart->getFinalTotal() == 0 && $this->request->get['mode'] != 'edit') {
                        $this->session->data['payment_method'] = [
                            'id'    => 'no_payment_required',
                            'title' => $this->language->get('no_payment_required'),
                        ];
                    } else {
                        $this->session->data['payment_method'] =
                            $this->session->data['payment_methods'][$this->request->post['payment_method']];
                    }
                    $this->session->data['comment'] = $this->request->post['comment'];
                    $this->extensions->hk_ProcessData($this);
                    abc_redirect($this->html->getSecureURL('checkout/guest_step_3'));
                }
            }
        }


        // Shipping Methods
        $shipping_methods = [];
        if ($this->cart->hasShipping()
            && (!isset($this->session->data['shipping_methods'])
                || !$this->config->get('config_shipping_session'))) {
            $shipping_methods = $this->checkout->getShippingList();
            $this->session->data['shipping_methods'] = $shipping_methods;
        }

        // Payment Methods
        $total = $this->cart->buildTotalDisplay();
        $this->data['order_totals'] = $total;

        $skip_step = false;

        //# If only 1 shipping and 1 payment it is set to be defaulted,
        // select and skip and redirect to checkout guest_step_3
        if (count($shipping_methods) == 1) {
            $shipping_method_name = key($shipping_methods);
            #Check config if we allowed to set this shipping and skip the step
            $ext_config = $this->model_checkout_extension->getSettings($shipping_method_name);
            if ($ext_config[$shipping_method_name . "_autoselect"]) {
                //take first quote. This needs to be accounted for if configure shipping to be autoselected
                if (sizeof($shipping_methods[$shipping_method_name]['quote']) == 1) {
                    $this->session->data['shipping_method'] = current(
                        $shipping_methods[$shipping_method_name]['quote']
                    );
                    $skip_step = true;
                }
            }
        } elseif (count($this->session->data['shipping_methods']) == 0) {
            //if not shipment, skip
            $skip_step = true;
        }

        $payment_methods = $this->checkout->getPaymentList();
        $this->session->data['payment_methods'] = $payment_methods;

        if ($skip_step && $this->request->get['mode'] != 'edit') {
            if (count($payment_methods) == 1) {
                $shipping_method_name = key($payment_methods);
                if ($payment_methods[$shipping_method_name]['settings'][$shipping_method_name . "_autoselect"]) {
                    $this->session->data['payment_method'] = $payment_methods[$shipping_method_name];
                    abc_redirect($this->html->getSecureURL('checkout/guest_step_3'));
                }
            }
        }

        $this->document->resetBreadcrumbs();
        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getHomeURL(),
                'text'      => $this->language->get('text_home'),
                'separator' => false,
            ]
        );
        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSecureURL($cart_rt),
                'text'      => $this->language->get('text_cart'),
                'separator' => $this->language->get('text_separator'),
            ]
        );
        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSecureURL('checkout/guest_step_1'),
                'text'      => $this->language->get('text_guest_step_1'),
                'separator' => $this->language->get('text_separator'),
            ]
        );
        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSecureURL('checkout/guest_step_2'),
                'text'      => $this->language->get('text_guest_step_2'),
                'separator' => $this->language->get('text_separator'),
            ]
        );

        $this->data['text_payment_methods'] = $this->language->get('text_payment_methods');
        $this->data['text_coupon'] = $this->language->get('text_coupon');
        $this->data['entry_coupon'] = $this->language->get('entry_coupon');

        if (isset($this->session->data['error'])) {
            $this->view->assign('error_warning', $this->session->data['error']);
            unset($this->session->data['error']);
        } elseif (isset($this->error['warning'])) {
            $this->view->assign('error_warning', $this->error['warning']);
        } else {
            $this->view->assign('error_warning', '');
        }

        $this->view->assign('success', $this->session->data['success']);
        if (isset($this->session->data['success'])) {
            unset($this->session->data['success']);
        }

        $action = $this->html->getSecureURL(
            'checkout/guest_step_2',
            ($this->request->get['mode'] ? '&mode=' . $this->request->get['mode'] : ''), true);

        if ($this->config->get('coupon_status')) {
            $this->data['coupon_status'] = $this->config->get('coupon_status');
            $coupon_form = $this->dispatch('blocks/coupon_codes', ['action' => $action]);
            $this->data['coupon_form'] = $coupon_form->dispatchGetOutput();
            unset($coupon_form);
        }

        $item = $this->html->buildElement(
            [
                'type'  => 'button',
                'name'  => 'change_address',
                'style' => 'button',
                'text'  => $this->language->get('button_change_address'),
            ]);
        $this->data['change_address'] = $item->getHTML();

        if (isset($this->session->data['shipping_methods']) && !$this->session->data['shipping_methods']) {
            $this->view->assign('error_warning', $this->language->get('error_no_shipping'));
        }

        $form = new AForm();
        $form->setForm(['form_name' => 'guest']);
        $this->data['form']['form_open'] = $form->getFieldHtml(
            [
                'type'   => 'form',
                'name'   => 'guest',
                'action' => $action,
                'csrf'   => true,
            ]
        );

        $this->data['shipping_methods'] = $this->session->data['shipping_methods'] ?: [];
        $shipping = isset($this->request->post['shipping_method'])
            ? $this->request->post['shipping_method']
            : $this->session->data['shipping_method']['id'];

        if ($this->data['shipping_methods']) {
            foreach ($this->data['shipping_methods'] as $k => $v) {
                if ($v['quote'] && is_array($v['quote'])) {
                    foreach ($v['quote'] as $key => $val) {
                        $this->data['shipping_methods'][$k]['quote'][$key]['radio'] = $form->getFieldHtml(
                            [
                                'type'    => 'radio',
                                'id'      => $val['id'],
                                'name'    => 'shipping_method',
                                'options' => [$val['id'] => ''],
                                'value'   => $shipping == $val['id'],
                            ]);
                    }
                }
            }
        } else {
            $this->data['shipping_methods'] = [];
        }

        $payment = $this->request->post['payment_method'] ?: $this->session->data['payment_method']['id'];

        if ($payment_methods) {
            if ($shipping_methods) {
                //build array with payments available per each shipping
                foreach ($shipping_methods as $shipping_method_name => $method_val) {
                    #Check config of selected shipping method and see if we have accepted payments restriction
                    $ship_ext_config = $this->model_checkout_extension->getSettings($shipping_method_name);
                    $accept_payment_ids = $ship_ext_config[$shipping_method_name . "_accept_payments"];
                    if (is_array($accept_payment_ids) && count($accept_payment_ids)) {
                        #filter only allowed payment methods
                        $ac_payments = [];
                        foreach ($payment_methods as $key => $res_payment) {
                            if (in_array($res_payment['extension_id'], $accept_payment_ids)) {
                                $ac_payments[$key] = $res_payment;
                            }
                        }
                    } else {
                        $ac_payments = $payment_methods;
                    }
                    foreach ($ac_payments as $key => $value) {
                        $selected = false;
                        if ($payment == $value['id']) {
                            $selected = true;
                        } elseif ($this->config->get($key . "_autoselect")) {
                            $selected = true;
                        }

                        $this->data['payment_methods'][$shipping_method_name][$key] = $value;
                        $this->data['payment_methods'][$shipping_method_name][$key]['radio'] = $form->getFieldHtml(
                            [
                                'type'    => 'radio',
                                'name'    => 'payment_method',
                                'options' => [
                                    $value['id'] => ''
                                ],
                                'value'   => $selected,
                            ]);
                    }
                }
            } else {
                //no shipping available show one set of payments
                foreach ($payment_methods as $key => $value) {
                    $selected = false;
                    if ($payment == $value['id']) {
                        $selected = true;
                    } elseif ($this->config->get($key . "_autoselect")) {
                        $selected = true;
                    }

                    $this->data['payment_methods']['no_shipping'][$key] = $value;
                    $this->data['payment_methods']['no_shipping'][$key]['radio'] = $form->getFieldHtml(
                        [
                            'type'    => 'radio',
                            'name'    => 'payment_method',
                            'options' => [
                                $value['id'] => ''
                            ],
                            'value'   => $selected,
                        ]);
                }
            }
        } else {
            $this->data['payment_methods'] = [];
        }

        $this->data['comment'] = $this->request->post['comment'] ?: $this->session->data['comment'];
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
                    '&content_id=' . $this->config->get('config_checkout_id')
                );
                $this->data['text_agree_href_text'] = $content_info['title'];
            } else {
                $this->data['text_agree'] = '';
            }
        } else {
            $this->data['text_agree'] = '';
        }

        if ($this->data['text_agree']) {
            $this->data['form']['agree'] = $form->getFieldHtml(
                [
                    'type'    => 'checkbox',
                    'name'    => 'agree',
                    'value'   => '1',
                    'checked' => (bool)$this->request->post['agree'],
                ]);
        }

        $this->data['agree'] = $this->request->post['agree'];
        $this->data['back'] = $this->html->getSecureURL('checkout/guest_step_1');
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
        $this->view->assign('buttons', $this->view->fetch('pages/checkout/payment.buttons.tpl'));

        $this->processTemplate('pages/checkout/guest_step_2.tpl');

        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function validate($post)
    {
        if ($this->cart->hasShipping()) {
            if (!isset($post['shipping_method']) || !$post['shipping_method']) {
                $this->error['warning'] = $this->language->get('error_shipping');
                return false;
            } else {
                $shipping = explode('.', $post['shipping_method']);

                if (!isset($this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]])) {
                    $this->error['warning'] = $this->language->get('error_shipping');
                    return false;
                }
            }
        }
        if ($this->cart->getFinalTotal()) {
            if (!isset($post['payment_method'])) {
                $this->error['warning'] = $this->language->get('error_payment');
                return false;
            } else {
                if (!isset($this->session->data['payment_methods'][$post['payment_method']])) {
                    $this->error['warning'] = $this->language->get('error_payment');
                    return false;
                }
            }
        }
        if ($this->config->get('config_checkout_id')) {
            $content_info = Content::getContent((int)$this->config->get('config_checkout_id'))?->toArray();
            if ($content_info) {
                if (!isset($post['agree'])) {
                    $this->error['warning'] = sprintf($this->language->get('error_agree'), $content_info['title']);
                    return false;
                }
            }
        }

        $this->extensions->hk_ValidateData($this, __FUNCTION__, $post);
        return (!$this->error);
    }

    protected function validateCoupon($post)
    {

        $this->loadLanguage('checkout/payment');
        $promotion = new APromotion();
        $coupon = $promotion->getCouponData($post['coupon']);
        if (!$coupon) {
            $this->error['warning'] = $this->language->get('error_coupon');
        }

        $this->extensions->hk_ValidateData($this, __FUNCTION__, $post);
        return (!$this->error);
    }
}