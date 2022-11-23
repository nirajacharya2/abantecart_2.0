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

use abc\core\engine\ASecureControllerAPI;
use abc\extensions\free_shipping\models\storefront\extension\ModelExtensionFreeShipping;
use abc\models\customer\Address;

class ControllerApiCheckoutShipping extends ASecureControllerAPI
{
    public $error = [];

    public function post()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $request = $this->rest->getRequestParams();

        if (!in_array($request['mode'], ['select', 'list'])) {
            $this->rest->sendResponse(400, ['error' => 'Incorrect request mode!']);
            return;
        }

        //load language from main section
        $this->loadLanguage('checkout/shipping');
        if ($request['mode'] == 'select' && $this->validate($request)) {
            $shipping = explode('.', $request['shipping_method']);
            $this->session->data['shipping_method'] =
                $this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]];
            $this->session->data['comment'] = strip_tags($request['comment']);

            //process data
            $this->extensions->hk_ProcessData($this);

            $this->rest->sendResponse(200, ['status' => 1, 'shipping_select' => 'success']);
            return null;
        }

        if (!$this->cart->hasProducts()) {
            //No products in the cart.
            $this->rest->sendResponse(200, ['status' => 2, 'error' => 'Nothing in the cart!']);
            return null;
        }

        if (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout')) {
            //No stock for products in the cart if tracked.
            $this->rest->sendResponse(200, ['status' => 3, 'error' => 'No stock for product!']);
            return null;
        }

        if (!$this->cart->hasShipping()) {
            unset(
                $this->session->data['shipping_address_id'],
                $this->session->data['shipping_method'],
                $this->session->data['shipping_methods']
            );

            $this->tax->setZone($this->session->data['country_id'], $this->session->data['zone_id']);
            $this->rest->sendResponse(
                200,
                [
                    'status'   => 0,
                    'shipping' => 'products do not require shipping'
                ]
            );
            return;
        }

        if (!isset($this->session->data['shipping_address_id'])) {
            $this->session->data['shipping_address_id'] = $this->customer->getAddressId();
        }

        if (!$this->session->data['shipping_address_id']) {
            //Problem. Missing shipping address
            $this->rest->sendResponse(
                200,
                [
                    'status' => 4,
                    'error'  => 'Missing shipping address!'
                ]
            );
            return;
        }

        $shipping_address = Address::find($this->session->data['shipping_address_id'])?->toArray();

        if (!$shipping_address) {
            //Problem. Missing shipping address
            $this->rest->sendResponse(
                400,
                [
                    'status' => 4,
                    'error'  => 'Inaccessible shipping address!'
                ]
            );
            return;
        }

        // if tax zone is taken from shipping address
        if (!$this->config->get('config_tax_customer')) {
            $this->tax->setZone($shipping_address['country_id'], $shipping_address['zone_id']);
        } else { // if tax zone is taken from billing address
            $address = Address::find($this->customer->getAddressId());
            if($address) {
                $this->tax->setZone($address->country_id, $address->zone_id);
            }
        }

        $this->loadModel('checkout/extension');

        if (!isset($this->session->data['shipping_methods']) || !$this->config->get('config_shipping_session')) {
            $quote_data = [];

            $results = $this->model_checkout_extension->getExtensions('shipping');
            foreach ($results as $result) {
                /** @var ModelExtensionFreeShipping|object $mdl */
                $mdl = $this->loadModel('extension/' . $result['key']);
                $quote = $mdl->getQuote($shipping_address);

                if ($quote) {
                    $quote_data[$result['key']] = [
                        'title'      => $quote['title'],
                        'quote'      => $quote['quote'],
                        'sort_order' => $quote['sort_order'],
                        'error'      => $quote['error'],
                    ];
                }
            }

            $sort_order = [];

            foreach ($quote_data as $key => $value) {
                $sort_order[$key] = $value['sort_order'];
            }

            array_multisort($sort_order, SORT_ASC, $quote_data);

            $this->session->data['shipping_methods'] = $quote_data;
        }

        $this->data['error_warning'] = $this->error['warning'];

        if (isset($this->session->data['shipping_methods']) && !$this->session->data['shipping_methods']) {
            $this->data['error_warning'] = $this->language->get('error_no_shipping');
        }

        $this->data['address'] = $this->customer->getFormattedAddress(
            $shipping_address,
            $shipping_address['address_format']
        );
        $this->data['shipping_methods'] = $this->session->data['shipping_methods'] ?: [];
        $this->data['comment'] = $request['comment'] ?? $this->session->data['comment'];

        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->rest->setResponseData($this->data);
        $this->rest->sendResponse(200);
    }

    public function validate($request)
    {
        if (!isset($request['shipping_method'])) {
            $this->error['warning'] = $this->language->get('error_shipping');
        } else {
            $shipping = explode('.', $request['shipping_method']);
            if (!isset($this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]])) {
                $this->error['warning'] = $this->language->get('error_shipping');
            }
        }

        //validate post data
        $this->extensions->hk_ValidateData($this);
        return !$this->error;
    }
}