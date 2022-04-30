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

namespace abc\controllers\admin;

use abc\core\engine\AController;

class ControllerCommonHome extends AController
{

    public function login($instance_id)
    {
        if (isset($this->request->get['rt']) && !isset($this->request->get['token'])) {
            $route = '';
            $part = explode('/', $this->request->get['rt']);
            if (isset($part[0])) {
                $route .= $part[0];
            }
            if (isset($part[1])) {
                $route .= '/'.$part[1];
            }

            $ignore = [
                'index/login',
                'index/logout',
                'index/forgot_password',
                'error/not_found',
                'error/permission',
            ];

            if (!in_array($route, $ignore)) {
                return $this->dispatch('pages/index/login');
            }
        } else {
            if (!isset($this->request->get['token'])
                || !isset($this->session->data['token'])
                || ($this->request->get['token'] != $this->session->data['token'])
            ) {
                //clear session data
                $this->session->clear();
                return $this->dispatch('pages/index/login');
            }
        }
        return null;
    }

    public function permission()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        if (isset($this->request->get['rt'])) {
            $route = '';
            $rt = $this->request->get['rt'];
            if (str_starts_with($rt, 'p/')) {
                $rt = substr($rt, 2);
            }
            $part = explode('/', $rt);

            if (isset($part[0])) {
                $route .= $part[0];
            }

            if (isset($part[1])) {
                $route .= '/'.$part[1];
            }

            $ignore = [
                'index/home',
                'index/login',
                'index/logout',
                'index/forgot_password',
                'index/edit_details',
                'error/not_found',
                'error/permission',
                'error/token',
            ];

            if (!in_array($route, $ignore)) {
                if (!$this->user->canAccess($route)) {
                    return $this->dispatch('pages/error/permission');
                }
            }
        }

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }
}