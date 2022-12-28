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

use abc\core\engine\ASecureControllerAPI;
use abc\models\customer\Customer;

if (!class_exists('abc\core\ABC')) {
    header('Location: static_pages/?forbidden='.basename(__FILE__));
}

class ControllerApiAccountEdit extends ASecureControllerAPI
{
    protected $v_error = [];
    public $data;

    /**
     * @OA\POST(
     *     path="/index.php/?rt=a/account/edit",
     *     summary="Edit step 2",
     *     description="There are 2 steps to edit customer details. First step is to get all allowed fields and provided earlier data (in case of error). Second step is to provide data to be validated and saved.",
     *     tags={"Account"},
     *     security={{"tokenAuth":{}, "apiKey":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/accountEditRequestModel"),
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success response",
     *         @OA\JsonContent(ref="#/components/schemas/EditStep2SuccessModel"),
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
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $request_data = $this->rest->getRequestParams();

        $this->loadLanguage('account/edit');
        $this->loadLanguage('account/success');
        $request_data['customer_id'] = $this->customer->getId();
        if ($request_data['email']) {
            $request_data['email_confirmation'] = $request_data['email'];
        }
        $this->data['errors'] = array_merge( $this->data['errors'] ?? [], $this->customer::validateRegistrationData($request_data) ?? []);
        if (count($this->data['errors']) === 0) {
            $request_data['newsletter'] = 1;
            $this->customer->model()->update($request_data);
            $this->data['status'] = 1;
            $this->data['success'] = $this->language->get('text_success');
            unset($this->data['errors']);

            //Update controller data
            $this->extensions->hk_UpdateData($this, __FUNCTION__);
            $this->rest->setResponseData($this->data);
            $this->rest->sendResponse(200);
        } else {
            $this->data['error_code'] = 0;
            //Update controller data
            $this->extensions->hk_UpdateData($this, __FUNCTION__);
            $this->data['errors'] = $this->mapErrorsAsArray($this->data['errors']);
            $this->rest->setResponseData($this->data);
            $this->rest->sendResponse(400);
        }
    }

    /**
     * @OA\Get (
     *     path="/index.php/?rt=a/account/edit",
     *     summary="Edit step 1",
     *     description="There are 2 steps to edit customer details. First step is to get all allowed fields and provided earlier data (in case of error). Second step is to provide data to be validated and saved.",
     *     tags={"Account"},
     *     security={{"tokenAuth":{}, "apiKey":{}}},
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
     *                     ref="#/components/schemas/EditFieldsModel"
     *                 )
     *             )
     *         ),
     *     )
     * )
     *
     */
    public function get()
    {
        return $this->buildResponse();
    }

    private function buildResponse()
    {
        //Get all required data fields for registration.
        $this->loadLanguage('account/create');
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $request_data = $this->rest->getRequestParams();

        $customer_info = Customer::getCustomer($this->customer->getId());

        if (isset($request_data['firstname'])) {
            $firstname = $request_data['firstname'];
        } elseif (isset($customer_info)) {
            $firstname = $customer_info['firstname'];
        } else {
            $firstname = '';
        }

        if (isset($request_data['lastname'])) {
            $lastname = $request_data['lastname'];
        } elseif (isset($customer_info)) {
            $lastname = $customer_info['lastname'];
        } else {
            $lastname = '';
        }

        if (isset($request_data['email'])) {
            $email = $request_data['email'];
        } elseif (isset($customer_info)) {
            $email = $customer_info['email'];
        } else {
            $email = '';
        }

        if (isset($request_data['telephone'])) {
            $telephone = $request_data['telephone'];
        } elseif (isset($customer_info)) {
            $telephone = $customer_info['telephone'];
        } else {
            $telephone = '';
        }

        if (isset($request_data['fax'])) {
            $fax = $request_data['fax'];
        } elseif (isset($customer_info)) {
            $fax = $customer_info['fax'];
        } else {
            $fax = '';
        }

        if (isset($request_data['newsletter'])) {
            $newsletter = $request_data['newsletter'];
        } elseif (isset($customer_info)) {
            $newsletter = $customer_info['newsletter'];
        } else {
            $newsletter = '';
        }

        $this->data['fields']['firstname'] = [
            'type'     => 'input',
            'name'     => 'firstname',
            'value'    => $firstname,
            'required' => true,
            'error'    => $this->v_error['firstname'],
        ];
        $this->data['fields']['lastname'] = [
            'type'     => 'input',
            'name'     => 'lastname',
            'value'    => $lastname,
            'required' => true,
            'error'    => $this->v_error['lastname'],
        ];
        $this->data['fields']['email'] = [
            'type'     => 'input',
            'name'     => 'email',
            'value'    => $email,
            'required' => true,
            'error'    => $this->v_error['email'],
        ];
        $this->data['fields']['telephone'] = [
            'type'  => 'input',
            'name'  => 'telephone',
            'value' => $telephone,
            'error' => $this->v_error['telephone'],
        ];
        $this->data['fields']['fax'] = [
            'type'     => 'input',
            'name'     => 'fax',
            'value'    => $fax,
            'required' => false,
        ];

        $this->data['fields']['newsletter'] = [
            'type'     => 'selectbox',
            'name'     => 'newsletter',
            'value'    => $newsletter,
            'required' => false,
        ];

        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->rest->setResponseData($this->data);
        $this->rest->sendResponse(200);
    }
}
