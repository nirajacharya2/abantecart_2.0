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
use abc\models\customer\Address;
use H;
use Illuminate\Validation\ValidationException;

if (!class_exists('abc\core\ABC')) {
    header('Location: static_pages/?forbidden='.basename(__FILE__));
}

class ControllerPagesCheckoutAddress extends AController
{
    public $errors = [];
    public $data = [];

    public function shipping()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $cart_rt = 'checkout/cart';
        //is this an embed mode
        if ($this->config->get('embed_mode') == true) {
            $cart_rt = 'r/checkout/cart/embed';
        }

        if (!$this->cart->hasProducts() || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            abc_redirect($this->html->getSecureURL($cart_rt));
        }

        if (!$this->cart->hasShipping()) {
            abc_redirect($this->html->getSecureURL($cart_rt));
        }

        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->html->getSecureURL('checkout/shipping');
            abc_redirect($this->html->getSecureURL('account/login'));
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
                'href'      => $this->html->getSecureURL('checkout/shipping'),
                'text'      => $this->language->get('text_shipping'),
                'separator' => $this->language->get('text_separator'),
            ]);

        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSecureURL('checkout/address/shipping'),
                'text'      => $this->language->get('text_address'),
                'separator' => $this->language->get('text_separator'),
            ]);

        if ($this->request->is_POST() && $this->csrftoken->isTokenValid()) {
            $post = $this->request->post;
            $post['customer_id'] = $this->customer->getId();
            if (isset($post['address_id'])) {
                $this->session->data['shipping_address_id'] = $post['address_id'];

                unset($this->session->data['shipping_methods'],
                      $this->session->data['shipping_method']);

                if ($this->cart->hasShipping()) {
                    $address = Address::find($post['address_id']);
                    if ($address) {
                        $this->tax->setZone($address->country_id, $address->zone_id);
                    }
                }
                unset($this->session->data['shipping_methods'],
                      $this->session->data['shipping_method']);
                $this->extensions->hk_ProcessData($this);
                abc_redirect($this->html->getSecureURL('checkout/shipping'));
            }

            $address = new Address($post);
            try {
                $address->validate($post);
            } catch (ValidationException $e) {
                H::SimplifyValidationErrors($address->errors()['validation'], $this->errors);
                $this->data['errors'] = $this->errors;
            }

            if (!$this->errors) {
                $address->save();
                $this->session->data['shipping_address_id'] = $address->address_id;

                unset($this->session->data['shipping_methods'], $this->session->data['shipping_method']);

                if ($this->cart->hasShipping()) {
                    $this->tax->setZone($this->request->post['country_id'], $this->request->post['zone_id']);
                }
                $this->extensions->hk_ProcessData($this);
                abc_redirect($this->html->getSecureURL('checkout/shipping'));
            }
        }
        $this->_getForm('shipping');

        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function payment()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $cart_rt = 'checkout/cart';
        //is this an embed mode
        if ($this->config->get('embed_mode') == true) {
            $cart_rt = 'r/checkout/cart/embed';
        }

        if (!$this->cart->hasProducts() || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            abc_redirect($this->html->getSecureURL($cart_rt));
        }

        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->html->getSecureURL('checkout/shipping');
            abc_redirect($this->html->getSecureURL('account/login'));
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

        if ($this->cart->hasShipping()) {
            $this->document->addBreadcrumb(
                [
                    'href'      => $this->html->getSecureURL('checkout/shipping'),
                    'text'      => $this->language->get('text_shipping'),
                    'separator' => $this->language->get('text_separator'),
                ]);
        }

        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSecureURL('checkout/payment'),
                'text'      => $this->language->get('text_payment'),
                'separator' => $this->language->get('text_separator'),
            ]);

        $this->document->addBreadcrumb(
            [
                'href'      => $this->html->getSecureURL('checkout/address/payment'),
                'text'      => $this->language->get('text_address'),
                'separator' => $this->language->get('text_separator'),
            ]);

        if ($this->request->is_POST() && $this->csrftoken->isTokenValid()) {
            if (isset($this->request->post['address_id'])) {
                $this->session->data['payment_address_id'] = $this->request->post['address_id'];

                unset($this->session->data['payment_methods']);
                unset($this->session->data['payment_method']);
                $this->extensions->hk_ProcessData($this);
                abc_redirect($this->html->getSecureURL('checkout/payment'));
            }

            $address = new Address($this->request->post);
            try {
                $address->validate($this->request->post);
            } catch (ValidationException $e) {
                H::SimplifyValidationErrors($address->errors()['validation'], $this->errors);
            }

            if (!$this->errors) {
                $address->save();
                $this->session->data['payment_address_id'] = $address->address_id;

                unset($this->session->data['payment_methods'],
                    $this->session->data['payment_method']);
                $this->extensions->hk_ProcessData($this);
                abc_redirect($this->html->getSecureURL('checkout/payment'));
            }
        }

        $this->_getForm('payment');

        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    protected function _getForm($type)
    {

        $this->data['heading_title'] = $this->language->get('text_'.$type).' '.$this->language->get('text_address');
        $this->data['default'] = $this->session->data[$type.'_address_id'];
        $form = new AForm();
        $form->setForm(['form_name' => 'address_1']);
        $this->data['form0']['form_open'] = $form->getFieldHtml(
            [
                'type'   => 'form',
                'name'   => 'address_1',
                'action' => $this->html->getSecureURL('checkout/address/'.$type),
                'csrf'   => true,
            ]
        );

        $addresses = [];
        $results = Address::getAddresses($this->customer->getId(), $this->language->getLanguageID())->toArray();

        foreach ($results as $result) {
            $addresses[] = [
                'address_id' => $result['address_id'],
                'address'    => $result['firstname'].' '.$result['lastname'].', '.$result['address_1'].', '
                    .$result['city'].', '.(($result['zone']) ? $result['zone'].', ' : false)
                    .(($result['postcode']) ? $result['postcode'].', ' : false).$result['country'],
                'href'       => $this->html->getSecureURL(
                                                    'account/address/'.$type,
                                                    'address_id='.$result['address_id']
                                ),
                'radio'      => $form->getFieldHtml(
                    [
                        'type'    => 'radio',
                        'id'      => 'a_'.$result['address_id'],
                        'name'    => 'address_id',
                        'options' => [$result['address_id'] => ''],
                        'value'   => ($result['address_id'] == $this->data['default'] ? $result['address_id'] : ''),
                    ]
                ),
            ];
        }
        $this->data['addresses'] = $addresses;

        $this->data['form0']['continue'] = $form->getFieldHtml(
            [
                'type' => 'submit',
                'name' => $this->language->get('button_continue'),
            ]);

        $form = new AForm();
        $form->setForm(['form_name' => 'Address2Frm']);
        $this->data['form']['form_open'] = $form->getFieldHtml(
            [
                'type'   => 'form',
                'name'   => 'Address2Frm',
                'action' => $this->html->getSecureURL('checkout/address/'.$type),
                'csrf'   => true,
            ]
        );

        $this->data['form']['fields']['firstname'] = $form->getFieldHtml([
            'type'     => 'input',
            'name'     => 'firstname',
            'value'    => $this->request->post['firstname'],
            'required' => true,
        ]);
        $this->data['form']['fields']['lastname'] = $form->getFieldHtml([
            'type'     => 'input',
            'name'     => 'lastname',
            'value'    => $this->request->post['lastname'],
            'required' => true,
        ]);
        $this->data['form']['fields']['company'] = $form->getFieldHtml(
            [
                'type'     => 'input',
                'name'     => 'company',
                'value'    => $this->request->post['company'],
                'required' => false,
            ]);
        $this->data['form']['fields']['address_1'] = $form->getFieldHtml(
            [
                'type'     => 'input',
                'name'     => 'address_1',
                'value'    => $this->request->post['address_1'],
                'required' => true,
            ]);
        $this->data['form']['fields']['address_2'] = $form->getFieldHtml(
            [
                'type'     => 'input',
                'name'     => 'address_2',
                'value'    => $this->request->post['address_2'],
                'required' => false,
            ]);
        $this->data['form']['fields']['city'] = $form->getFieldHtml(
            [
                'type'     => 'input',
                'name'     => 'city',
                'value'    => $this->request->post['city'],
                'required' => true,
            ]);

        $this->data['form']['fields']['zone'] = $form->getFieldHtml(
            [
                'type'     => 'selectbox',
                'name'     => 'zone_id',
                'required' => true,
            ]);

        $this->data['form']['fields']['postcode'] = $form->getFieldHtml(
            [
                'type'     => 'input',
                'name'     => 'postcode',
                'value'    => $this->request->post['postcode'],
                'required' => true,
            ]);
        $this->loadModel('localisation/country');
        $countries = $this->model_localisation_country->getCountries();
        $options = ["FALSE" => $this->language->get('text_select')];
        foreach ($countries as $item) {
            $options[$item['country_id']] = $item['name'];
        }
        $this->data['form']['fields']['country_id'] = $form->getFieldHtml(
            [
                'type'     => 'selectbox',
                'name'     => 'country_id',
                'options'  => $options,
                'value'    => (isset($this->request->post['country_id']) ? $this->request->post['country_id'] : $this->config->get('config_country_id')),
                'required' => true,
            ]);

        $this->data['form']['continue'] = $form->getFieldHtml(
            [
                'type' => 'submit',
                'name' => $this->language->get('button_continue'),
            ]);

        $this->data['zone_id'] = isset($this->request->post['zone_id']) ? $this->request->post['zone_id'] : 'FALSE';

        $this->loadModel('localisation/country');
        $this->data['countries'] = $this->model_localisation_country->getCountries();

        $this->view->batchAssign($this->data);
        if ($this->config->get('embed_mode') == true) {
            //load special headers
            $this->addChild('responses/embed/head', 'head');
            $this->addChild('responses/embed/footer', 'footer');
            $this->processTemplate('embed/checkout/address.tpl');
        } else {
            $this->processTemplate('pages/checkout/address.tpl');
        }
    }

}