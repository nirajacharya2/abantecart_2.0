<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright 2011-2022 Belavier Commerce LLC

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

use abc\core\engine\AControllerAPI;
use H;

if (!class_exists('abc\core\ABC')) {
    header('Location: static_pages/?forbidden='.basename(__FILE__));
}

class ControllerApiCommonAccess extends AControllerAPI
{

    public $ignoredControllers = [
        'api/index/login',
        'api/common/access',
        'api/error/not_found',
        'api/error/no_access',
        'api/error/no_permission',
    ];
    public function main()
    {
        //check if any restriction on caller IP
        if (!$this->validateIP()) {
            $this->dispatch('api/error/no_access');
            return;
        }
        $headers = $this->request->getHeaders();
        //backward compatibility
        if($headers) {
            $headers['X-App-Api-Key'] = $headers['X-App-Api-Key'] ?: $this->request->post_or_get('api_key');
        }

        //validate if API enabled and KEY matches.
        if ($this->config->get('config_admin_api_status')) {
            if ($this->config->get('config_admin_api_key')
                && $this->config->get('config_admin_api_key') === $headers['X-App-Api-Key']) {
                return;
            } else {
                if (!$this->config->get('config_admin_api_key')) {
                    return;
                }
            }
        }
        $this->dispatch('api/error/no_access');
    }

    private function validateIP()
    {
        if (!H::has_value($this->config->get('config_admin_access_ip_list'))) {
            return true;
        }

        $ips = array_map('trim', explode(",", $this->config->get('config_admin_access_ip_list')));
        if (in_array($this->request->getRemoteIP(), $ips)) {
            return true;
        }
        return false;
    }

    public function login()
    {
        $request = $this->rest->getRequestParams();
        //allow access to listed controllers with no login
        if (isset($request['rt']) && !isset($request['token'])) {
            $route = '';
            $request['rt'] = ltrim($request['rt'], 'a/');
            $request['rt'] = ltrim($request['rt'], 'r/');
            $request['rt'] = ltrim($request['rt'], 'p/');
            $part = explode('/', $request['rt']);

            if (isset($part[0])) {
                $route .= $part[0];
            }
            if (isset($part[1])) {
                $route .= '/'.$part[1];
            }

            if (!in_array($route, $this->ignoredControllers)) {
                return $this->dispatch('api/index/login');
            }
        } else {
            if (!$this->user->isLoggedWithToken($request['token'])) {
                return $this->dispatch('api/index/login');
            }
        }
        return false;
    }

    public function permission()
    {
        $request = $this->rest->getRequestParams();

        if ($this->extensions->isExtensionController($request['rt'])) {
            return false;
        }

        if (isset($request['rt'])) {
            $route = '';
            $request['rt'] = ltrim($request['rt'], 'a/');
            $request['rt'] = ltrim($request['rt'], 'r/');
            $request['rt'] = ltrim($request['rt'], 'p/');
            $part = explode('/', $request['rt']);

            if (isset($part[0])) {
                $route .= $part[0];
            }
            if (isset($part[1])) {
                $route .= '/'.$part[1];
            }

            if (!in_array($route, $this->ignoredControllers)) {
                if (!$this->user->canAccess($route)) {
                    return $this->dispatch('api/error/no_permission');
                }
            }
        }
        return false;
    }
}