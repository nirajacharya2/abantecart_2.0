<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2019 Belavier Commerce LLC

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

use abc\core\engine\AControllerAPI;
use abc\models\customer\Address;
use abc\models\locale\Zone;
use Illuminate\Validation\ValidationException;

/**
 * Class ControllerApiCheckoutAddress
 *
 * @package abc\controllers\storefront
  */
class ControllerApiCheckoutAddress extends AControllerAPI
{
    public $error = [];
    public $data = [];

    public function post()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $request = $this->rest->getRequestParams();

        if (!$this->customer->isLoggedWithToken($request['token'])) {
            $this->rest->sendResponse(401, ['error' => 'Not logged in or Login attempt failed!']);
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

        //load language from main section
        $this->loadLanguage('checkout/address');

        if ($request['action'] == 'remove') {
            if (isset($request['address_id'])) {
                if (
                    Address::where('customer_id', '=', $this->customer->getId())->get()->count() == 1
                ) {
                    $this->error['warning'] = $this->language->get('error_delete');
                }
                if ($this->customer->getAddressId() == $this->request->get['address_id']) {
                    $this->error['warning'] = $this->language->get('error_default');
                }
                if (!$this->error) {
                    $address = Address::find($request['address_id']);
                    if($address && $address->customer_id == $this->customer->getId()){
                        $address->forceDelete();
                        $this->rest->sendResponse(
                            200,
                            [
                                'status' => 1,
                                'error' => 'address removed'
                            ]
                        );
                        return null;
                    }else{
                        $this->error['warning'] = $this->language->get('error_delete');
                    }
                } else {
                    $this->rest->sendResponse(
                        200,
                        [
                            'status' => 0,
                            'error' => 'deletion of default address not allowed'
                        ]
                    );
                    return null;
                }
            } else {
                if (!isset($request['address_id'])) {
                    $this->rest->sendResponse(200, ['status' => 0, 'error' => 'address id missing ']);
                    return null;
                }
            }
        }

        if ($request['mode'] == 'shipping') {
            if (!$this->cart->hasShipping()) {
                $this->rest->sendResponse(200, ['status' => 0, 'shipping' => 'products do not require shipping']);
                return null;
            }

            if (isset($request['address_id'])) {
                $this->session->data['shipping_address_id'] = $request['address_id'];
                unset($this->session->data['shipping_methods'],$this->session->data['shipping_method']);

                if ($this->cart->hasShipping()) {
                    $address = Address::find($request['address_id']);
                    if ($address) {
                        $this->tax->setZone($address->country_id, $address->zone_id);
                    }
                }

                $this->rest->sendResponse(200, ['status' => 1, 'shipping' => 'shipping address selected']);
                return null;
            }

            if ($request['action'] == 'save') {

                $address_id = $this->addAddress($request);
                if($address_id){
                    $this->session->data['shipping_address_id'] = $address_id;
                    unset($this->session->data['shipping_methods'],$this->session->data['shipping_method']);

                    if ($this->cart->hasShipping()) {
                        $this->tax->setZone($request['country_id'], $request['zone_id']);
                    }

                    $this->rest->sendResponse(200, ['status' => 1, 'shipping' => 'shipping address selected']);
                    return null;
                }
            }

            $this->data['selected_address_id'] = $this->session->data['shipping_address_id'];
            $this->buildResponseData($request);

        } else {
            if ($request['mode'] == 'payment') {
                if (isset($request['address_id'])) {
                    $this->session->data['payment_address_id'] = $request['address_id'];
                    unset($this->session->data['payment_methods']);
                    unset($this->session->data['payment_method']);
                    $this->rest->sendResponse(200, ['status' => 1, 'payment' => 'payment address selected']);
                    return null;
                }

                if ($request['action'] == 'save') {

                    $address_id = $this->addAddress($request);
                    if($address_id){
                        $this->session->data['payment_address_id'] = $address_id;
                        unset($this->session->data['payment_methods'],$this->session->data['payment_method']);

                        if ($this->cart->hasShipping()) {
                            $this->tax->setZone($request['country_id'], $request['zone_id']);
                        }

                        $this->rest->sendResponse(200, ['status' => 1, 'payment' => 'payment address selected']);
                        return null;
                    }
                }

                $this->data['selected_address_id'] = $this->session->data['payment_address_id'];
                $this->buildResponseData($request);
            }
        }

        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->rest->setResponseData($this->data);
        $this->rest->sendResponse(200);
    }

    protected function buildResponseData($request_data)
    {

        $addresses = [];
        $results = Address::getAddresses($this->customer->getId(), $this->language->getLanguageID())
                          ->toArray();

        foreach ($results as $result) {
            $addresses[] = [
                'address_id' => $result['address_id'],
                'address'    => $result['firstname'].' '.$result['lastname'].', '
                    .$result['address_1'].', '
                    .$result['city'].', '
                    .(($result['zone']) ? $result['zone'].', ' : false)
                    .(($result['postcode']) ? $result['postcode'].', ' : false)
                    .$result['country'],
            ];
        }
        $this->data['saved_addresses'] = $addresses;

        //Build data before response
        if ($this->error) {
            $this->data['status'] = 'error';
            $this->data['error_firstname'] = $this->error['firstname'];
            $this->data['error_lastname'] = $this->error['lastname'];
            $this->data['error_address_1'] = $this->error['address_1'];
            $this->data['error_city'] = $this->error['city'];
            $this->data['error_country'] = $this->error['country'];
            $this->data['error_zone'] = $this->error['zone'];
        }

        $this->data['fields']['firstname'] = [
            'type'     => 'input',
            'name'     => 'firstname',
            'value'    => $request_data['firstname'],
            'required' => true,
            'error'    => $this->error['firstname'],
        ];

        $this->data['fields']['lastname'] = [
            'type'     => 'input',
            'name'     => 'lastname',
            'value'    => $request_data['lastname'],
            'required' => true,
            'error'    => $this->error['lastname'],
        ];

        $this->data['fields']['company'] = [
            'type'     => 'input',
            'name'     => 'company',
            'value'    => $request_data['company'],
            'required' => false,
        ];

        $this->data['fields']['address_1'] = [
            'type'     => 'input',
            'name'     => 'address_1',
            'value'    => $request_data['address_1'],
            'required' => true,
            'error'    => $this->error['address_1'],
        ];

        $this->data['fields']['address_2'] = [
            'type'     => 'input',
            'name'     => 'address_2',
            'value'    => $request_data['address_2'],
            'required' => false,
        ];

        $this->data['fields']['city'] = [
            'type'     => 'input',
            'name'     => 'city',
            'value'    => $request_data['city'],
            'required' => true,
            'error'    => $this->error['city'],
        ];

        $this->data['fields']['postcode'] = [
            'type'     => 'input',
            'name'     => 'postcode',
            'value'    => $request_data['postcode'],
            'required' => false,
        ];

        $this->loadModel('localisation/country');
        $countries = $this->model_localisation_country->getCountries();
        $options = ["FALSE" => $this->language->get('text_select')];
        foreach ($countries as $item) {
            $options[$item['country_id']] = $item['name'];
        }

        $this->data['fields']['country_id'] = [
            'type'     => 'selectbox',
            'name'     => 'country_id',
            'options'  => $options,
            'value'    => (
            isset($request_data['country_id'])
                ? $request_data['country_id']
                : $this->config->get('config_country_id')
            ),
            'required' => true,
            'error'    => $this->error['country_id'],
        ];

        $this->data['fields']['zone_id'] = [
            'type'     => 'selectbox',
            'name'     => 'zone_id',
            'required' => true,
            'value'    => $request_data['zone_id'],
            'error'    => $this->error['lastname'],
        ];
    }

    protected function addAddress($data)
    {
        $this->error = [];
        //add customer_id into data
        $data['customer_id'] = $this->customer->getId();
        $address = new Address();
        try{
            $messages = [];
            foreach(['firstname', 'lastname', 'address_1', 'city', 'postcode', 'country_id', 'zone_id'] as $fieldName) {
                $messages[$fieldName] = $this->language->get('error_'.rtrim($fieldName, '_id'));
            }
            $address->validate($data, $messages);
            //check if pair county-zone exists
            $exists = Zone::where('country_id', '=', $data['country_id'])
                            ->where('zone_id', '=', $data['zone_id'])
                            ->get()->count();
            if(!$exists){
                $this->error['zone'] = $this->language->get('error_zone');
            }
        }catch(ValidationException $e){
            $this->error = $address->errors()['validation'];
            $this->error['warning'] = $this->language->get('gen_data_entry_error');
        }

        if (!$this->error) {
            $address->fill($data)->save();
            return $address->address_id;
        }
        return false;
    }
}
