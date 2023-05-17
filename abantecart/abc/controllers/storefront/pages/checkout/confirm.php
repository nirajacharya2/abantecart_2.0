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
use abc\core\engine\AResource;
use abc\core\lib\CheckOut;
use abc\models\content\Content;

/**
 * Class ControllerPagesCheckoutConfirm
 *
 * @package abc\controllers\storefront
 * @property CheckOut $checkout
 */
class ControllerPagesCheckoutConfirm extends AController
{
    public $error = [];

    public function main()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $session =& $this->session->data;
        $cart_rt = 'checkout/cart';
        $shipping_rt = 'checkout/shipping';
        $payment_rt = 'checkout/payment';
        $login_rt = 'account/login';
        $pmt_address_rt = 'checkout/address/payment';
        $shp_address_rt = 'checkout/address/shipping';
        $confirm_rt = 'checkout/confirm';
        $success_rt = 'checkout/success';
        $product_rt = 'product/product';
        if ($this->config->get('embed_mode')) {
            $cart_rt = 'r/checkout/cart/embed';
        }

        if (!$this->cart->hasProducts() || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            abc_redirect($this->html->getSecureURL($cart_rt));
        }

        //validate if order min/max are met
        if (!$this->cart->hasMinRequirement() || !$this->cart->hasMaxRequirement()) {
            abc_redirect($this->html->getSecureURL($cart_rt));
        }

        if (!$this->customer->isLogged()) {
            $session['redirect'] = $this->html->getSecureURL($shipping_rt);
            abc_redirect($this->html->getSecureURL($login_rt));
        }

        if ($this->cart->hasShipping()) {
            if (!$this->checkout->getShippingAddress()) {
                abc_redirect($this->html->getSecureURL($shipping_rt));
            }

            if (!isset($session['shipping_method'])) {
                abc_redirect($this->html->getSecureURL($shipping_rt));
            }
        } else {
            unset($session['shipping_address_id'], $session['shipping_method'], $session['shipping_methods']);
            $this->checkout->setShippingMethod(null);
            $this->checkout->setShippingAddress(null);
            $this->tax->setZone($session['country_id'], $session['zone_id']);
        }

        if (!$this->checkout->getPaymentAddress()) {
            abc_redirect($this->html->getSecureURL($payment_rt));
        }

        if (!isset($session['payment_method'])) {
            abc_redirect($this->html->getSecureURL($payment_rt));
        }

        if ($this->request->get['balance'] == 'disapply') {
            unset($session['used_balance'],
                $this->request->get['balance'],
                $session['used_balance_full']
            );
        }
        $this->data = [];

        $this->data = $this->checkout->getOrder()->buildOrderData($session);
        $order_id = $this->checkout->getOrder()->saveOrder();

        if ($order_id === false) {
            // preventing rebuilding order of already processed orders
            //(by "back" button via browser history from external payment page(paypal, google_checkout etc))
            abc_redirect($this->html->getSecureURL($success_rt));
        }
        $session['order_id'] = $order_id;

        $this->document->setTitle($this->language->get('heading_title'));
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
                'text'      => $this->language->get('text_basket'),
                'separator' => $this->language->get('text_separator'),
            ]
        );

        if ($this->cart->hasShipping()) {
            $this->document->addBreadcrumb(
                [
                    'href'      => $this->html->getSecureURL($shipping_rt),
                    'text'      => $this->language->get('text_shipping'),
                    'separator' => $this->language->get('text_separator'),
                ]
            );
        }

        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSecureURL($payment_rt, '&mode=edit', true),
                'text'      => $this->language->get('text_payment'),
                'separator' => $this->language->get('text_separator'),
            ]
        );

        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSecureURL($confirm_rt),
                'text'      => $this->language->get('text_confirm'),
                'separator' => $this->language->get('text_separator'),
            ]
        );

        $this->data['error_warning'] = $this->error['warning'];
        $this->data['success'] = $session['success'];
        if (isset($session['success'])) {
            unset($session['success']);
        }

        //balance
        $balance_def_currency = $this->customer->getBalance();
        $balance = $this->currency->convert($balance_def_currency, $this->config->get('config_currency'),
            $session['currency']);

        if ($balance != 0
            || ($balance == 0 && $this->config->get('config_zero_customer_balance'))
            && (float)$session['used_balance'] != 0) {
            $this->data['balance'] = $this->language->get('text_balance_checkout')
                . ' ' . $this->currency->format($balance, $session['currency'], 1);
            if ((float)$session['used_balance'] > 0) {
                $this->data['disapply_balance'] = [
                    'href' => $this->html->getSecureURL($payment_rt, '&mode=edit&balance=disapply', true),
                    'text' => $this->language->get('button_disapply_balance'),
                ];
                $this->data['balance'] .= ' (' . $this->currency->format(
                        $balance_def_currency - (float)$session['used_balance']
                    )
                    . ')';
                $this->data['balance'] .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'
                    . $this->currency->format((float)$session['used_balance']) . ' '
                    . $this->language->get('text_applied_balance');
            } elseif ((float)$session['used_balance'] == 0 && $balance > 0) {
                $this->data['disapply_balance'] = [
                    'href' => $this->html->getSecureURL($payment_rt, '&mode=edit&balance=apply', true),
                    'text' => $this->language->get('button_apply_balance'),
                ];
            }
        }


        $shipping_address = $this->checkout->getShippingAddress();
        $shipping_method = $this->checkout->getShipping();
        if ($this->cart->hasShipping()) {
            $this->data['shipping_address'] = $this->customer->getFormattedAddress(
                $shipping_address,
                $shipping_address['address_format']
            );
        } else {
            $this->data['shipping_address'] = '';
        }

        $this->data['shipping_method'] = $shipping_method['title'];
        $this->data['shipping_method_price'] = $shipping_method['title'];
        $this->data['checkout_shipping_edit'] = $this->html->getSecureURL($shipping_rt, '&mode=edit', true);
        $this->data['checkout_shipping_address'] = $this->html->getSecureURL($shp_address_rt);

        $payment_method = $this->checkout->getPayment();
        $payment_address = $this->checkout->getPaymentAddress();
        if ($payment_address) {
            $this->data['payment_address'] = $this->customer->getFormattedAddress(
                $payment_address,
                $payment_address['address_format']
            );
        } else {
            $this->data['payment_address'] = '';
        }
        if ($payment_method['id'] != 'no_payment_required') {
            $this->data['payment_method'] = $payment_method['title'];
            $this->addChild('responses/extension/' . $payment_method['id'], 'payment');
        } else {
            $this->data['payment_method'] = '';
            $this->addChild('responses/checkout/no_payment', 'payment');
        }

        $this->data['checkout_payment_edit'] = $this->html->getSecureURL($payment_rt, '&mode=edit', true);
        $this->data['checkout_payment_address'] = $this->html->getSecureURL($pmt_address_rt);

        $this->loadModel('tool/seo_url');
        $this->loadModel('tool/image');

        $product_ids = array_column($this->data['products'], 'product_id');

        $resource = new AResource('image');
        $thumbnails = $resource->getMainThumbList(
            'products',
            $product_ids,
            $this->config->get('config_image_cart_width'),
            $this->config->get('config_image_cart_height')
        );

        //Format product data specific for confirmation page
        foreach ($this->data['products'] as $i => $product) {
            $product_id = $this->data['products'][$i]['product_id'];
            $opts = $this->data['products'][$i]['option'];
            $options = [];

            $thumbnail = $thumbnails[$product_id];

            foreach ($opts as $option) {
                //hide hidden options
                if ($option['element_type'] == 'H') {
                    continue;
                }
                $title = '';
                $value = $option['value'];
                // hide binary value for checkbox
                if ($option['element_type'] == 'C' && in_array($value, [0, 1])) {
                    $value = '';
                }
                // strip long textarea value
                if ($option['element_type'] == 'T') {
                    $title = strip_tags($value);
                    $title = str_replace('\r\n', "\n", $title);
                    $value = str_replace('\r\n', "\n", $value);
                    if (mb_strlen($value) > 64) {
                        $value = mb_substr($value, 0, 64) . '...';
                    }
                }
                $options[] = [
                    'name'  => $option['name'],
                    'value' => $value,
                    'title' => $title,
                ];
                // product image by option value
                $mSizes = [
                    'main'  =>
                        [
                            'width'  => $this->config->get('config_image_cart_width'),
                            'height' => $this->config->get('config_image_cart_height')
                        ],
                    'thumb' => [
                        'width'  => $this->config->get('config_image_cart_width'),
                        'height' => $this->config->get('config_image_cart_height')
                    ],
                ];


                $main_image = $resource->getResourceAllObjects(
                    'product_option_value',
                    $option['product_option_value_id'],
                    $mSizes,
                    1,
                    false
                );

                if (!empty($main_image)) {
                    $thumbnail['origin'] = $main_image['origin'];
                    $thumbnail['title'] = $main_image['title'];
                    $thumbnail['description'] = $main_image['description'];
                    $thumbnail['thumb_html'] = $main_image['thumb_html'];
                    $thumbnail['thumb_url'] = $main_image['thumb_url'];
                }
            }

            $this->data['products'][$i]['option'] = $options;

            $tax = $this->tax->calcTotalTaxAmount(
                $this->data['products'][$i]['total'],
                $this->data['products'][$i]['tax_class_id']
            );
            $price = $this->data['products'][$i]['price'];
            $qty = $this->data['products'][$i]['quantity'];
            $this->data['products'][$i] = array_merge(
                $this->data['products'][$i],
                [
                    'thumb' => $thumbnail,
                    'tax'   => $this->currency->format($tax),
                    'price' => $this->currency->format($price),
                    'total' => $this->currency->format_total($price, $qty),
                    'href'  => $this->html->getSEOURL($product_rt, '&product_id=' . $product_id, true),
                ]
            );
        }

        $display_totals = $this->cart->buildTotalDisplay();
        $this->data['totals'] = $display_totals['total_data'];

        $this->data['cart'] = $this->html->getSecureURL($cart_rt);

        if ($this->config->get('config_checkout_id')) {
            $content_info = Content::getContent((int)$this->config->get('config_checkout_id'))?->toArray();
            if ($content_info) {
                $this->data['text_accept_agree'] = $this->language->get('text_accept_agree');
                $this->data['text_accept_agree_href'] = $this->html->getURL(
                    'r/content/content/loadInfo',
                    '&content_id=' . $this->config->get('config_checkout_id'),
                    true
                );
                $this->data['text_accept_agree_href_link'] = $content_info['title'];
            } else {
                $this->data['text_accept_agree'] = '';
            }
        } else {
            $this->data['text_accept_agree'] = '';
        }

        if ($this->config->get('coupon_status')) {
            $this->data['coupon_status'] = $this->config->get('coupon_status');
        }

        $this->view->batchAssign($this->data);
        if ($this->config->get('embed_mode')) {
            //load special headers
            $this->addChild('responses/embed/head', 'head');
            $this->addChild('responses/embed/footer', 'footer');
            $this->processTemplate('embed/checkout/confirm.tpl');
        } else {
            $this->processTemplate('pages/checkout/confirm.tpl');
        }

        //update data before render
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }
}
