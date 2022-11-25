<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2022 Belavier Commerce LLC
 *
 * This source file is subject to Open Software License (OSL 3.0)
 * License details is bundled with this package in the file LICENSE.txt.
 * It is also available at this URL:
 * <http://www.opensource.org/licenses/OSL-3.0>
 *
 * UPGRADE NOTE:
 * Do not edit or add to this file if you wish to upgrade AbanteCart to newer
 * versions in the future. If you wish to customize AbanteCart for your
 * needs please refer to http://www.abantecart.com for more information.
 */

namespace abc\controllers\admin;

use abc\core\ABC;
use abc\core\engine\AControllerAPI;

if (!ABC::env('IS_ADMIN')) {
    header('Location: static_pages/?forbidden='.basename(__FILE__));
}

class ControllerApiIndexLogin extends AControllerAPI
{
    public function get()
    {
        $request = $this->rest->getRequestParams();
        $this->_validate_token($request['token']);
    }

    public function post()
    {
        //This is a login attempt
        $request = $this->rest->getRequestParams();
        if (trim($request['token'])) {
            //this is the request to authorized
            $this->_validate_token($request['token']);
        } else {
            if (isset($request['username']) && isset($request['password']) && $this->_validate($request['username'], $request['password'])) {
                if (!session_id()) {
                    $this->rest->setResponseData(['status' => 0, 'error' => 'Unable to get session ID.']);
                    $this->rest->sendResponse(501);
                    return null;
                }
                $this->session->data['token'] = session_id();
                $this->rest->setResponseData(['status' => 1, 'success' => 'Logged in', 'token' => $this->session->data['token']]);
                $this->rest->sendResponse(200);
            } else {
                $this->rest->setResponseData(['status' => 0, 'error' => 'Login attempt failed!']);
                $this->rest->sendResponse(401);
            }
        }
    }

    private function _validate($username, $password)
    {
        if (isset($username) && isset($password) && !$this->user->login($username, $password)) {
            $this->loadLanguage('common/login');
            $this->messages->saveNotice("API ".$this->language->get('error_login_message').$this->request->getRemoteIP(), $this->language->get('error_login_message_text').$username);
            return false;
        } else {
            return true;
        }
    }

    private function _validate_token($token)
    {
        if (isset($token) && $this->user->isLoggedWithToken($token)) {
            $this->rest->setResponseData(['status' => 1, 'request' => 'authorized']);
            $this->rest->sendResponse(200);
        } else {
            $this->rest->setResponseData(['status' => 0, 'request' => 'unauthorized ']);
            $this->rest->sendResponse(401);
        }
    }
}