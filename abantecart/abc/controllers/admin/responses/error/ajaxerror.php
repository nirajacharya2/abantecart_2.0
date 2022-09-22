<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2020 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/

namespace abc\controllers\admin;

use abc\core\engine\AController;
use abc\core\lib\AError;

class ControllerResponsesErrorAjaxError extends AController
{

    public function main()
    {
        //build default error response
        $this->loadLanguage('error/error');
        $error = new AError('');
        $err_data = [
            'error_title' => $this->language->get('heading_title'),
            'error_text'  => $this->language->get('text_error'),
        ];
        return $error->toJSONResponse('ERROR_400', $err_data);
    }

    public function validation($err_text = '')
    {
        //build validation error response
        $this->loadLanguage('error/error');
        $error = new AError('');
        $err_data = [
            'error_title' => $this->language->get('heading_title'),
            'error_text'  => ($err_text ? $err_text : $this->language->get('text_error')),
        ];
        return $error->toJSONResponse('VALIDATION_ERROR_406', $err_data);
    }

    public function permission()
    {
        //build permission error response
        $this->loadLanguage('error/permission');
        $error = new AError('');
        $err_data = [
            'error_title' => $this->language->get('heading_title'),
            'error_text'  => $this->language->get('text_permission'),
            'show_dialog' => true,
        ];
        return $error->toJSONResponse('NO_PERMISSIONS_403', $err_data);
    }

    public function login()
    {
        //build login error response
        $this->loadLanguage('error/login');
        $error = new AError('');
        $err_data = [
            'error_title' => $this->language->get('heading_title'),
            'error_text'  => $this->language->get('text_login'),
            'show_dialog' => true,
            'reload_page' => true,
        ];
        return $error->toJSONResponse('LOGIN_FAILED_401', $err_data);
    }

    public function not_found()
    {
        //build not_found response
        $this->loadLanguage('error/error');
        $error = new AError('');
        $err_data = [
            'error_title' => $this->language->get('heading_title'),
            'error_text'  => $this->language->get('text_page_not_found'),
        ];
        return $error->toJSONResponse('NOT_FOUND_404', $err_data);
    }
}