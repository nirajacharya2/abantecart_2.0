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
 * Class ControllerPagesCheckoutGuestStep3
 *
 * @package abc\controllers\storefront
 * @property Checkout $checkout
 */
class ControllerPagesCheckoutGuestStep3 extends AController
{
    public $error = [];

    public function main()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        //is this an embed mode
        $cart_rt = 'checkout/cart';
        $shipping_rt = 'checkout/shipping';
        $gs1_rt = 'checkout/guest_step_1';
        $gs2_rt = 'checkout/guest_step_2';
        $gs3_rt = 'checkout/guest_step_3';

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

        if ($this->customer->isLogged()) {
            abc_redirect($this->html->getSecureURL($shipping_rt));
        }

        if (!isset($this->session->data['guest'])) {
            abc_redirect($this->html->getSecureURL($gs1_rt));
        }

        if ($this->cart->hasShipping()) {
            if (!$this->checkout->getShipping()) {
                abc_redirect($this->html->getSecureURL($gs2_rt));
            }
        } else {
            unset(
                $this->session->data['shipping_method'],
                $this->session->data['shipping_methods']
            );
            $this->checkout->setShippingMethod(null);
            $this->tax->setZone($this->session->data['country_id'], $this->session->data['zone_id']);
        }

        if (!$this->checkout->getPayment()) {
            abc_redirect($this->html->getSecureURL($gs2_rt));
        }

        $this->loadLanguage('checkout/confirm');

        $this->document->setTitle($this->language->get('heading_title'));

        //build and save order
        $this->data = [];

        $this->data = $this->checkout->getOrder()->buildOrderData($this->session->data);
        $order_id = $this->checkout->getOrder()->saveOrder();
        $this->session->data['order_id'] = $order_id;

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

        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSecureURL($gs1_rt),
                'text'      => $this->language->get('text_guest_step_1'),
                'separator' => $this->language->get('text_separator'),
            ]
        );

        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSecureURL($gs2_rt),
                'text'      => $this->language->get('text_guest_step_2'),
                'separator' => $this->language->get('text_separator'),
            ]
        );

        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSecureURL($gs3_rt),
                'text'      => $this->language->get('text_confirm'),
                'separator' => $this->language->get('text_separator'),
            ]
        );

        $this->view->assign('error_warning', $this->error['warning']);
        $this->view->assign('success', $this->session->data['success']);
        if (isset($this->session->data['success'])) {
            unset($this->session->data['success']);
        }

        if ($this->cart->hasShipping()) {
            $shipping_address = $this->checkout->getShippingAddress();
            $this->data['shipping_address'] = $this->customer->getFormattedAddress(
                $shipping_address,
                $shipping_address['address_format']
            );
            $shipping_method = $this->checkout->getShipping();
            if (isset($shipping_method['title'])) {
                $this->data['shipping_method'] = $shipping_method['title'];
            } else {
                $this->data['shipping_method'] = '';
            }
        } else {
            $this->data['shipping_address'] = '';
        }

        $this->data['checkout_shipping'] = $this->html->getSecureURL($gs2_rt);
        $this->data['checkout_shipping_edit'] = $this->html->getSecureURL($gs2_rt, '&mode=edit', true);
        $this->data['checkout_shipping_address'] = $this->html->getSecureURL($gs1_rt);

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

        $this->data['checkout_payment'] = $this->html->getSecureURL($gs2_rt);
        $this->data['checkout_payment_edit'] = $this->html->getSecureURL($gs2_rt, '&mode=edit', true);
        $this->data['cart'] = $this->html->getSecureURL($cart_rt);
        $this->data['checkout_payment_address'] = $this->html->getSecureURL($gs1_rt);

        $this->loadModel('tool/seo_url');

        $product_ids = array_column($this->data['products'], 'product_id');

        //Format product data specific for confirmation page
        $resource = new AResource('image');
        $thumbnails = $resource->getMainThumbList(
            'products',
            $product_ids,
            $this->config->get('config_image_cart_width'),
            $this->config->get('config_image_cart_height')
        );

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

        foreach ($this->data['products'] as $product) {
            if (isset($product['option']) && !empty($product['option'])) {
                foreach ($product['option'] as $option) {
                    $main_image =
                        $resource->getResourceAllObjects('product_option_value', $option['product_option_value_id'], $mSizes, 1, false);
                }
                if (!empty($main_image)) {
                    $thumbnails[$product['key']] = $main_image;
                }
            }
        }


        for ($i = 0; $i < sizeof($this->data['products']); $i++) {
            $product_id = $this->data['products'][$i]['product_id'];
            if ($thumbnails[$this->data['products'][$i]['key']]) {
                $thumbnail = $thumbnails[$this->data['products'][$i]['key']];
            } else {
                $thumbnail = $thumbnails[$product_id];
            }
            $tax = $this->tax->calcTotalTaxAmount(
                $this->data['products'][$i]['total'],
                $this->data['products'][$i]['tax_class_id']
            );
            $price = $this->data['products'][$i]['price'];
            $quantity = $this->data['products'][$i]['quantity'];
            $this->data['products'][$i] = array_merge(
                $this->data['products'][$i],
                [
                    'thumb' => $thumbnail,
                    'tax'   => $this->currency->format($tax),
                    'price' => $this->currency->format($price),
                    'total' => $this->currency->format_total($price, $quantity),
                    'href'  => $this->html->getSEOURL('product/product', '&product_id=' . $product_id, true),
                ]
            );
        }

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
        $this->processTemplate('pages/checkout/confirm.tpl');

        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }
}
