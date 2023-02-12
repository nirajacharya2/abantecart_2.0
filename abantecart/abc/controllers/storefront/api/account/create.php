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

use abc\core\ABC;
use abc\core\engine\AControllerAPI;
use abc\core\lib\AMail;
use abc\models\content\Content;


/**
 * Class ControllerApiAccountCreate
 *
 * @package abc\controllers\storefront
 */
class ControllerApiAccountCreate extends AControllerAPI
{
    protected $v_error = [];

    /**
     * @OA\POST(
     *     path="/index.php/?rt=a/account/create",
     *     summary="Create step 2",
     *     description="There are 2 steps to register new customer and save customer details. First step is to get all required fields and provided earlier data (in case of error). Second step is to provide data to be validated and saved.",
     *     tags={"Account"},
     *     security={{"apiKey":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/accountCreateRequestModel"),
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success response",
     *         @OA\JsonContent(ref="#/components/schemas/CreateStep2SuccessModel"),
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Bad Request",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"),
     *     ),
     *     @OA\Response(
     *         response="403",
     *         description="Access denied",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"),
     *     ),
     *      @OA\Response(
     *         response="500",
     *         description="Server Error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"),
     *     )
     * )
     *
     */
    public function post()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        //only support post params for create account
        $request_data = $this->rest->getRequestParams();

        if ($this->customer->isLoggedWithToken($request_data['token'])) {
            $this->rest->setResponseData([
                    'error_code' => 0,
                    'error_title' => 'Access denied',
                    'error_text' => 'Already Logged in. Can not create new account.'
                ]);
            $this->rest->sendResponse(403);
            return null;
        }

        $this->loadLanguage('account/create');
        $this->loadLanguage('account/success');

        $this->v_error = $this->customer::validateRegistrationData($request_data);
        if (!$this->v_error) {
            $customer_data = $request_data;
            $customer_data['store_id'] = $this->config->get('config_store_id');
            if(!$customer_data['customer_group_id']){
                $customer_data['customer_group_id'] = (int)$this->config->get( 'config_customer_group_id' );
            }
            $this->customer::createCustomer($customer_data);
            unset($this->session->data['guest']);

            $this->customer->login($request_data['email'], $request_data['password']);

            $this->loadLanguage('mail/account_create');

            $subject = sprintf($this->language->get('text_subject'), $this->config->get('store_name'));

            $message = sprintf($this->language->get('text_welcome'), $this->config->get('store_name'))."\n\n";

            if (!$this->config->get('config_customer_approval')) {
                $message .= $this->language->get('text_login')."\n";
            } else {
                $message .= $this->language->get('text_approval')."\n";
            }

            $message .= $this->html->getSecureURL('account/login')."\n\n";
            $message .= $this->language->get('text_services')."\n\n";
            $message .= $this->language->get('text_thanks')."\n";
            $message .= $this->config->get('store_name');

            $mail = new AMail($this->config);
            $mail->setTo($request_data['email']);
            $mail->setFrom($this->config->get('store_main_email'));
            $mail->setSender($this->config->get('store_name'));
            $mail->setSubject($subject);
            $mail->setText(html_entity_decode($message, ENT_QUOTES, ABC::env('APP_CHARSET')));
            $mail->send();

            $this->data['status'] = 1;
            if (!$this->config->get('config_customer_approval')) {
                $this->data['text_message'] = sprintf($this->language->get('text_message'), '');
            } else {
                $this->data['text_message'] = sprintf(
                    $this->language->get('text_approval'),
                    $this->config->get('store_name'),
                    ''
                );
            }

            //Update controller data
            $this->extensions->hk_UpdateData($this, __FUNCTION__);
            $this->rest->setResponseData($this->data);
            $this->rest->sendResponse(200);
        } else {
            $this->data['error_code'] = 0;
            $this->data['errors'] = $this->v_error;

            //Update controller data
            $this->extensions->hk_UpdateData($this, __FUNCTION__);
            $this->rest->setResponseData($this->data);
            $this->rest->sendResponse(400);
        }

    }

    /**
     * @OA\Get (
     *     path="/index.php/?rt=a/account/create",
     *     summary="Create step 1",
     *     description="There are 2 steps to register new customer and save customer details. First step is to get all required fields and provided earlier data (in case of error). Second step is to provide data to be validated and saved.",
     *     tags={"Account"},
     *     security={{"apiKey":{}}},
     *     @OA\Response(
     *         response="200",
     *         description="Account data",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="fields",
     *                     type="object",
     *                     ref="#/components/schemas/CreateFieldsModel"
     *                 ),
     *                 @OA\Property(
     *                     property="text_agree",
     *                     type="string"
     *                 ),
     *             )
     *         ),
     *     )
     * )
     *
     */
    public function get()
    {
        //Get all required data fields for registration.
        $this->loadLanguage('account/create');
        $this->extensions->hk_InitData($this, __FUNCTION__);

        if ($this->config->get('prevent_email_as_login')) {
            // require login name
            $this->data['fields']['loginname'] = [
                'type'     => 'input',
                'name'     => 'loginname',
                'value'    => $this->request->post['loginname'],
                'required' => true,
                'error'    => $this->v_error['firstname'],
            ];
        }

        $this->data['fields']['firstname'] = [
            'type'     => 'input',
            'name'     => 'firstname',
            'value'    => $this->request->post['firstname'],
            'required' => true,
            'error'    => $this->v_error['firstname'],
        ];
        $this->data['fields']['lastname'] = [
            'type'     => 'input',
            'name'     => 'lastname',
            'value'    => $this->request->post['lastname'],
            'required' => true,
            'error'    => $this->v_error['lastname'],
        ];
        $this->data['fields']['email'] = [
            'type'     => 'input',
            'name'     => 'email',
            'value'    => $this->request->post['email'],
            'required' => true,
            'error'    => $this->v_error['email'],
        ];
        $this->data['fields']['telephone'] = [
            'type'  => 'input',
            'name'  => 'telephone',
            'value' => $this->request->post['telephone'],
            'error' => $this->v_error['telephone'],
        ];
        $this->data['fields']['fax'] = [
            'type'     => 'input',
            'name'     => 'fax',
            'value'    => $this->request->post['fax'],
            'required' => false,
        ];
        $this->data['fields']['company'] = [
            'type'     => 'input',
            'name'     => 'company',
            'value'    => $this->request->post['company'],
            'required' => false,
        ];
        $this->data['fields']['address_1'] = [
            'type'     => 'input',
            'name'     => 'address_1',
            'value'    => $this->request->post['address_1'],
            'required' => true,
            'error'    => $this->v_error['address_1'],
        ];
        $this->data['fields']['address_2'] = [
            'type'     => 'input',
            'name'     => 'address_2',
            'value'    => $this->request->post['address_2'],
            'required' => false,
        ];
        $this->data['fields']['city'] = [
            'type'     => 'input',
            'name'     => 'city',
            'value'    => $this->request->post['city'],
            'required' => true,
            'error'    => $this->v_error['city'],
        ];
        $this->data['fields']['postcode'] = [
            'type'     => 'input',
            'name'     => 'postcode',
            'value'    => $this->request->post['postcode'],
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
            isset($this->request->post['country_id'])
                ? $this->request->post['country_id']
                : $this->config->get('config_country_id')
            ),
            'required' => true,
            'error'    => $this->v_error['country_id'],
        ];
        $this->data['fields']['zone_id'] = [
            'type'     => 'selectbox',
            'name'     => 'zone_id',
            'required' => true,
            'value'    => $this->request->post['zone_id'],
            'error'    => $this->v_error['lastname'],
        ];

        $this->data['fields']['password'] = [
            'type'     => 'password',
            'name'     => 'password',
            'value'    => $this->request->post['password'],
            'required' => true,
            'error'    => $this->v_error['lastname'],
        ];
        $this->data['fields']['confirm'] = [
            'type'     => 'password',
            'name'     => 'confirm',
            'value'    => $this->request->post['confirm'],
            'required' => true,
            'error'    => $this->v_error['lastname'],
        ];
        $this->data['fields']['newsletter'] = [
            'type'    => 'radio',
            'name'    => 'newsletter',
            'value'   => (isset($this->request->post['newsletter']) ? $this->request->post['newsletter'] : -1),
            'options' => [
                '1' => $this->language->get('text_yes'),
                '0' => $this->language->get('text_no'),
            ],
        ];

        $this->data['fields']['agree'] = [
            'type'    => 'checkbox',
            'name'    => 'agree',
            'value'   => 1,
            'checked' => false,
        ];

        if ($this->config->get('config_account_id')) {
            $content_info = Content::getContent($this->config->get('config_account_id'))?->toArray();
            if ($content_info) {
                $text_agree = sprintf($this->language->get('text_agree'),
                    $this->html->getURL(
                        'r/content/content/loadInfo',
                        '&content_id='.$this->config->get('config_account_id')
                    ),
                    $content_info['title']);
            } else {
                $text_agree = '';
            }
        } else {
            $text_agree = '';
        }
        $this->data['text_agree'] = $text_agree;

        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->rest->setResponseData($this->data);
        $this->rest->sendResponse(200);
    }
}