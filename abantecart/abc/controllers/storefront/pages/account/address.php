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

namespace abc\controllers\storefront;

use abc\core\engine\AController;
use abc\core\engine\AForm;
use abc\models\customer\Address;
use Illuminate\Validation\ValidationException;

/**
 * Class ControllerPagesAccountAddress
 *
 * @package abc\controllers\storefront
 */
class ControllerPagesAccountAddress extends AController
{
    protected $error = [];
    public $data = [];

    public function main()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->checkAccess();

        $this->document->setTitle($this->language->get('heading_title'));

        $this->getList();

        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
        unset($this->session->data['success']);
    }

    /**
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     */
    public function insert()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->checkAccess();

        $this->document->setTitle($this->language->get('heading_title'));

        if ($this->request->is_POST() && $this->validateForm()) {
            $post = $this->request->post;
            $post['customer_id'] = $this->customer->getId();
            $address = new Address($post);
            $address->save();
            $this->data['address_id'] = $address->address_id;
            $this->session->data['success'] = $this->language->get('text_insert');

            $this->extensions->hk_ProcessData($this);
            abc_redirect($this->html->getSecureURL('account/address'));
        }

        $this->getForm();

        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function update()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->checkAccess();

        $this->document->setTitle($this->language->get('heading_title'));

        if ($this->request->is_POST() && $this->validateForm()) {
            $address = Address::find($this->request->get['address_id']);
            if($address){
                $address->update($this->request->post);
            }

            if (isset($this->session->data['shipping_address_id'])
                && $this->request->get['address_id'] == $this->session->data['shipping_address_id']
            ) {
                unset($this->session->data['shipping_methods']);
                unset($this->session->data['shipping_method']);

                $this->tax->setZone($this->request->post['country_id'], $this->request->post['zone_id']);
            }

            if (isset($this->session->data['payment_address_id'])
                && $this->request->get['address_id'] == $this->session->data['payment_address_id']
            ) {
                unset($this->session->data['payment_methods']);
                unset($this->session->data['payment_method']);
            }

            $this->session->data['success'] = $this->language->get('text_update');

            $this->extensions->hk_ProcessData($this);

            abc_redirect($this->html->getSecureURL('account/address'));
        }

        $this->getForm();

        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function delete()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->checkAccess();

        $this->document->setTitle($this->language->get('heading_title'));

        if (isset($this->request->get['address_id']) && $this->validateDelete()) {
            Address::destroy($this->request->get['address_id']);

            if (isset($this->session->data['shipping_address_id'])
                && ($this->request->get['address_id'] == $this->session->data['shipping_address_id'])) {
                unset($this->session->data['shipping_address_id']);
                unset($this->session->data['shipping_methods']);
                unset($this->session->data['shipping_method']);
            }

            if (isset($this->session->data['payment_address_id'])
                && ($this->request->get['address_id'] == $this->session->data['payment_address_id'])) {
                unset($this->session->data['payment_address_id']);
                unset($this->session->data['payment_methods']);
                unset($this->session->data['payment_method']);
            }

            $this->session->data['success'] = $this->language->get('text_delete');
            $this->extensions->hk_ProcessData($this);
            abc_redirect($this->html->getSecureURL('account/address'));
        }

        $this->getList();

        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    protected function getList()
    {
        $this->document->addBreadcrumb([
            'href'      => $this->html->getHomeURL(),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);

        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('account/account'),
            'text'      => $this->language->get('text_account'),
            'separator' => $this->language->get('text_separator'),
        ]);

        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('account/address'),
            'text'      => $this->language->get('heading_title'),
            'separator' => $this->language->get('text_separator'),
        ]);

        $this->view->assign('error_warning', $this->error['warning']);
        $this->view->assign('success', $this->session->data['success']);

        $results = Address::getAddresses($this->customer->getId(), $this->language->getLanguageID());
        $results = $results ? $results->toArray() : [];
        $addresses = [];
        foreach ($results as $result) {
            $formattedAddress = $this->customer->getFormattedAddress($result, $result['address_format']);

            $edit = $this->html->buildElement(
                [
                    'type'  => 'button',
                    'text'  => $this->language->get('button_edit'),
                    'style' => 'button btn-primary',
                    'icon'  => 'fa-edit fa',
                    'attr'  => 'onclick="location = \''.$this->html->getSecureURL('account/address/update',
                            '&address_id='.$result['address_id']).'\'" ',
                ]);
            $delete = $this->html->buildElement(
                [
                    'type'  => 'button',
                    'text'  => $this->language->get('button_delete'),
                    'style' => '',
                    'icon'  => 'fa fa-remove',
                    'attr'  => 'onclick="location = \''.$this->html->getSecureURL('account/address/delete',
                            '&address_id='.$result['address_id']).'\'" ',
                ]);
            $addresses[] = [
                'address_id'    => $result['address_id'],
                'address'       => $formattedAddress,
                'button_edit'   => $edit,
                'button_delete' => $delete,
                'default'       => ($this->customer->getAddressId() == $result['address_id']),
            ];
        }

        $this->view->assign('addresses', $addresses);
        $this->view->assign('insert', $this->html->getSecureURL('account/address/insert'));

        $insert = $this->html->buildElement(
            [
                'type'  => 'button',
                'name'  => 'insert',
                'text'  => $this->language->get('button_new_address'),
                'icon'  => 'fa fa-plus',
                'style' => 'button',
            ]);
        $this->view->assign('button_insert', $insert);

        $back = $this->html->buildElement(
            [
                'type'  => 'button',
                'name'  => 'back',
                'text'  => $this->language->get('button_back'),
                'icon'  => 'fa fa-arrow-left',
                'style' => 'button',
            ]);
        $this->view->assign('button_back', $back);
        $this->view->assign('back', $this->html->getSecureURL('account/account'));

        $this->processTemplate('pages/account/addresses.tpl');
    }

    protected function getForm()
    {
        $this->document->resetBreadcrumbs();

        $this->document->addBreadcrumb([
            'href'      => $this->html->getHomeURL(),
            'text'      => $this->language->get('text_home'),
            'separator' => false,
        ]);

        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('account/account'),
            'text'      => $this->language->get('text_account'),
            'separator' => $this->language->get('text_separator'),
        ]);

        $this->document->addBreadcrumb([
            'href'      => $this->html->getSecureURL('account/address'),
            'text'      => $this->language->get('heading_title'),
            'separator' => $this->language->get('text_separator'),
        ]);

        if (!isset($this->request->get['address_id'])) {
            $this->document->addBreadcrumb([
                'href'      => $this->html->getSecureURL('account/address/insert'),
                'text'      => $this->language->get('text_edit_address'),
                'separator' => $this->language->get('text_separator'),
            ]);
        } else {
            $this->document->addBreadcrumb([
                'href'      => $this->html->getSecureURL(
                                            'account/address/update',
                                            '&address_id='.$this->request->get['address_id']
                               ),
                'text'      => $this->language->get('text_edit_address'),
                'separator' => $this->language->get('text_separator'),
            ]);
        }

        foreach($this->error as $k => $text){
            $key = $k=='warning' ? 'error_'.$k : 'error_message_'.$k;
            $this->view->assign($key, $text);
        }

        if (isset($this->request->get['address_id']) && $this->request->is_GET()) {
            $address = Address::find($this->request->get['address_id']);
            if($address) {
                $address_info = $address->toArray();
            }else{
                abc_redirect($this->html->getSecureURL('account/address'));
            }
        }

        $this->data['back'] = $this->html->getSecureURL('account/address');

        $form = new AForm();
        $form->setForm(['form_name' => 'AddressFrm']);

        if (!isset($this->request->get['address_id'])) {
            $action = $this->html->getSecureURL('account/address/insert');
        } else {
            $action =
                $this->html->getSecureURL('account/address/update', '&address_id='.$this->request->get['address_id']);
        }
        $this->data['form']['form_open'] = $form->getFieldHtml(
            [
                'type'   => 'form',
                'name'   => 'AddressFrm',
                'action' => $action,
                'csrf'   => true,
            ]
        );

        if (isset($this->request->post['firstname'])) {
            $firstname = $this->request->post['firstname'];
        } elseif (isset($address_info)) {
            $firstname = $address_info['firstname'];
        } else {
            $firstname = '';
        }
        $this->data['form']['fields']['firstname'] = $form->getFieldHtml([
            'type'     => 'input',
            'name'     => 'firstname',
            'value'    => $firstname,
            'required' => true,
        ]);

        if (isset($this->request->post['lastname'])) {
            $lastname = $this->request->post['lastname'];
        } elseif (isset($address_info)) {
            $lastname = $address_info['lastname'];
        } else {
            $lastname = '';
        }
        $this->data['form']['fields']['lastname'] = $form->getFieldHtml([
            'type'     => 'input',
            'name'     => 'lastname',
            'value'    => $lastname,
            'required' => true,
        ]);

        if (isset($this->request->post['company'])) {
            $company = $this->request->post['company'];
        } elseif (isset($address_info)) {
            $company = $address_info['company'];
        } else {
            $company = '';
        }
        $this->data['form']['fields']['company'] = $form->getFieldHtml([
            'type'     => 'input',
            'name'     => 'company',
            'value'    => $company,
            'required' => false,
        ]);

        if (isset($this->request->post['address_1'])) {
            $address_1 = $this->request->post['address_1'];
        } elseif (isset($address_info)) {
            $address_1 = $address_info['address_1'];
        } else {
            $address_1 = '';
        }
        $this->data['form']['fields']['address_1'] = $form->getFieldHtml([
            'type'     => 'input',
            'name'     => 'address_1',
            'value'    => $address_1,
            'required' => true,
        ]);

        if (isset($this->request->post['address_2'])) {
            $address_2 = $this->request->post['address_2'];
        } elseif (isset($address_info)) {
            $address_2 = $address_info['address_2'];
        } else {
            $address_2 = '';
        }
        $this->data['form']['fields']['address_2'] = $form->getFieldHtml([
            'type'     => 'input',
            'name'     => 'address_2',
            'value'    => $address_2,
            'required' => false,
        ]);

        if (isset($this->request->post['city'])) {
            $city = $this->request->post['city'];
        } elseif (isset($address_info)) {
            $city = $address_info['city'];
        } else {
            $city = '';
        }
        $this->data['form']['fields']['city'] = $form->getFieldHtml([
            'type'     => 'input',
            'name'     => 'city',
            'value'    => $city,
            'required' => true,
        ]);

        if (isset($this->request->post['zone_id'])) {
            $this->data['zone_id'] = $this->request->post['zone_id'];
        } elseif (isset($address_info)) {
            $this->data['zone_id'] = $address_info['zone_id'];
        } else {
            $this->data['zone_id'] = 'FALSE';
        }

        $this->data['form']['fields']['zone'] = $form->getFieldHtml([
            'type'     => 'selectbox',
            'name'     => 'zone_id',
            'value'    => $this->data['zone_id'],
            'required' => true,
        ]);
        if (isset($this->request->post['default'])) {
            $default = $this->request->post['default'];
        } elseif (isset($this->request->get['address_id'])) {
            $default = $this->customer->getAddressId() == $this->request->get['address_id'];
        } else {
            $default = false;
        }

        if (isset($this->request->post['postcode'])) {
            $postcode = $this->request->post['postcode'];
        } elseif (isset($address_info)) {
            $postcode = $address_info['postcode'];
        } else {
            $postcode = '';
        }
        $this->data['form']['fields']['postcode'] = $form->getFieldHtml([
            'type'     => 'input',
            'name'     => 'postcode',
            'value'    => $postcode,
            'required' => true,
        ]);
        if (isset($this->request->post['country_id'])) {
            $country_id = $this->request->post['country_id'];
        } elseif (isset($address_info)) {
            $country_id = $address_info['country_id'];
        } else {
            $country_id = $this->config->get('config_country_id');
        }

        $this->loadModel('localisation/country');
        $countries = $this->model_localisation_country->getCountries();
        $options = ["FALSE" => $this->language->get('text_select')];
        foreach ($countries as $item) {
            $options[$item['country_id']] = $item['name'];
        }
        $this->data['entry_zone_id'] = $this->language->get('entry_zone');
        $this->data['entry_country_id'] = $this->language->get('entry_country');
        $this->data['form']['fields']['country_id'] = $form->getFieldHtml([
            'type'     => 'selectbox',
            'name'     => 'country_id',
            'options'  => $options,
            'value'    => $country_id,
            'required' => true,
        ]);

        $this->data['form']['default'] = $form->getFieldHtml([
            'type'    => 'radio',
            'name'    => 'default',
            'value'   => $default,
            'options' => [
                '1' => $this->language->get('text_yes'),
                '0' => $this->language->get('text_no'),
            ],
        ]);
        $this->data['form']['back'] = $form->getFieldHtml([
            'type'  => 'button',
            'name'  => 'back',
            'text'  => $this->language->get('button_back'),
            'icon'  => 'fa fa-arrow-left',
            'style' => 'button',
        ]);
        $this->data['form']['submit'] = $form->getFieldHtml([
            'type' => 'submit',
            'icon' => 'fa fa-check',
            'name' => $this->language->get('button_continue'),
        ]);

        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/account/address.tpl');
    }

    protected function validateForm()
    {
        $address = new Address();
        try{
            $address->validate($this->request->post);
        }catch(ValidationException $e){
            \H::SimplifyValidationErrors($address->errors()['validation'], $this->error);
        }

        $this->extensions->hk_ValidateData($this);

        if (!$this->csrftoken->isTokenValid()) {
            $this->error['warning'] = $this->language->get('error_unknown');
        }

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }

    protected function validateDelete()
    {
        $total = Address::where('customer_id', '=', $this->customer->getId())->get()->count();
        if ($total == 1) {
            $this->error['warning'] = $this->language->get('error_delete');
        }

        if ($this->customer->getAddressId() == $this->request->get['address_id']) {
            $this->error['warning'] = $this->language->get('error_default');
        }

        $this->extensions->hk_ValidateData($this);

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }

    protected function checkAccess()
    {
        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->html->getSecureURL('account/address');
            abc_redirect($this->html->getSecureURL('account/login'));
        }
    }
}