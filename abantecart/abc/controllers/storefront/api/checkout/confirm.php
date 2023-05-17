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

use abc\core\engine\AResource;
use abc\core\engine\ASecureControllerAPI;
use abc\core\lib\AJson;
use abc\models\content\Content;
use abc\models\customer\Address;

class ControllerApiCheckoutConfirm extends ASecureControllerAPI
{
    public $error = [];

    public function post()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $request = $this->rest->getRequestParams();
        $session =& $this->session->data;

        if (!$this->cart->hasProducts()) {
            //No products in the cart.
            $this->rest->sendResponse(400, ['status' => 2, 'error' => 'Nothing in the cart!']);
            return;
        }
        if (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout')) {
            //No stock for products in the cart if tracked.
            $this->rest->sendResponse(400, ['status' => 3, 'error' => 'No stock for product!']);
            return;
        }

        if ($this->cart->hasShipping()) {
            if (!isset($session['shipping_address_id']) || !$session['shipping_address_id']) {
                //Problem. Missing shipping address
                $this->rest->sendResponse(406, ['status' => 4, 'error' => 'Missing shipping address!']);
                return;
            }

            if (!isset($session['shipping_method'])) {
                //Problem. Missing shipping address
                $this->rest->sendResponse(406, ['status' => 5, 'error' => 'Missing shipping method!']);
                return;
            }
        } else {
            unset($this->session->data['shipping_address_id']);
            unset($this->session->data['shipping_method']);
            unset($this->session->data['shipping_methods']);

            $this->tax->setZone($session['country_id'], $session['zone_id']);
        }

        if (!isset($session['payment_address_id']) || !$session['payment_address_id']) {
            $this->rest->sendResponse(406, ['status' => 6, 'error' => 'Missing payment (billing) address!']);
            return;
        }

        if (!isset($session['payment_method'])) {
            $this->rest->sendResponse(406, ['status' => 5, 'error' => 'Missing payment (billing) method!']);
            return;
        }

        //build order and pre-save
        $this->data = $this->checkout->getOrder()->buildOrderData($session);
        $session['order_id'] = $this->checkout->getOrder()->saveOrder();

        //build confirmation data
        $shipping_address = [];
        if ($address = Address::find($session['shipping_address_id'])) {
            $shipping_address = $address->toArray();
        }

        if ($this->cart->hasShipping()) {
            $this->data['shipping_address'] = $this->customer->getFormattedAddress(
                $shipping_address,
                $shipping_address['address_format']
            );
        } else {
            $this->data['shipping_address'] = '';
        }

        $this->data['shipping_method'] = $session['shipping_method']['title'];

        $payment_address = [];
        if ($address = Address::find($session['payment_address_id'])) {
            $payment_address = $address->toArray();
        }
        if ($payment_address) {
            $this->data['payment_address'] = $this->customer->getFormattedAddress(
                $payment_address,
                $payment_address['address_format']
            );
        } else {
            $this->data['payment_address'] = '';
        }

        if ($session['payment_method']['id'] != 'no_payment_required') {
            $this->data['payment_method'] = $session['payment_method']['title'];
        } else {
            $this->data['payment_method'] = '';
        }

        $this->loadModel('tool/seo_url');
        $this->loadModel('tool/image');
        $this->load->library('json');

        $product_ids = array_column((array)$this->data['products'], 'product_id');

        $resource = new AResource('image');
        $thumbnails = $resource->getMainThumbList(
            'products',
            $product_ids,
            $this->config->get('config_image_cart_width'),
            $this->config->get('config_image_cart_height')
        );

        //Format product data specific for confirmation response
        for ($i = 0; $i < sizeof($this->data['products']); $i++) {
            $product_id = $this->data['products'][$i]['product_id'];
            $thumbnail = $thumbnails[$product_id];

            $tax = $this->tax->calcTotalTaxAmount(
                $this->data['products'][$i]['total'],
                $this->data['products'][$i]['tax_class_id']
            );

            $price = $this->data['products'][$i]['price'];
            $quantity = $this->data['products'][$i]['quantity'];
            $this->data['products'][$i] = array_merge(
                $this->data['products'][$i],
                [
                    'thumb' => $thumbnail['thumb_url'],
                    'tax'   => $this->currency->format($tax),
                    'price' => $this->currency->format($price),
                    'total' => $this->currency->format_total($price, $quantity),
                ]
            );
        }

        if ($this->config->get('config_checkout_id')) {
            $contentInfo = Content::getContent((int)$this->config->get('config_checkout_id'))?->toArray();
            if ($contentInfo) {
                $this->data['text_accept_agree'] = sprintf(
                    $this->language->get('text_accept_agree'),
                    '',
                    $contentInfo['description']['title']
                );
            } else {
                $this->data['text_accept_agree'] = '';
            }
        } else {
            $this->data['text_accept_agree'] = '';
        }

        // Load selected payment required data from payment extension
        if ($this->session->data['payment_method']['id'] != 'no_payment_required') {
            $payment_controller = $this->dispatch(
                'responses/extension/' . $this->session->data['payment_method']['id'] . '/api'
            );
        } else {
            $payment_controller = $this->dispatch('responses/checkout/no_payment/api');
        }

        $this->data['payment'] = AJson::decode($payment_controller->dispatchGetOutput(), true);
        //set process_rt for process step to run the payment
        $session['process_rt'] = $this->data['payment']['process_rt'];
        //mark confirmation viewed
        $session['confirmed'] = true;

        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->rest->setResponseData($this->data);
        $this->rest->sendResponse(200);
    }
}