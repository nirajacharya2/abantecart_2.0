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
use abc\core\engine\AController;
use abc\core\lib\AJson;
use abc\extensions\banner_manager\models\Banner;
use abc\extensions\banner_manager\models\BannerStat;

class ControllerResponsesExtensionBannerManager extends AController
{

    // save banner statistic
    public function main()
    {
        //default controller function to register view or click
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $banner_id = (int)$this->request->get['banner_id'];
        //type of registered activity 1 = view and 2 = click
        $type = (int)$this->request->get['type'];

        if ($banner_id) {
            BannerStat::create(
                [
                    'banner_id' => $banner_id,
                    'type'      => $type,
                    'store_id'  => $this->config->get('config_store_id'),
                    'user_info' => [
                        'user_id'   => $this->customer?->getId(),
                        'user_ip'   => $this->request->getRemoteIP(),
                        'user_host' => $this->request->server['REMOTE_HOST'],
                        'page_rt'   => $this->request->get['page_rt'],
                    ]
                ]
            );
        }

        $this->data['output'] = [];
        $this->data['output']['success'] = 'OK';
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->load->library('json');
        $this->response->setOutput(AJson::encode($this->data['output']));
    }

    public function click()
    {
        //controller function to register click and redirect
        //NOTE: Work only for banners with target_url
        //For security reason, do not allow URL as parameter for this redirect
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $banner_id = (int)$this->request->get['banner_id'];
        $url = ABC::env('INDEX_FILE');
        //register click
        if ($banner_id) {
            $banner = Banner::find($banner_id);
            $url = $banner->target_url;
            if (!$url || str_starts_with($url, '#')) {
                $url = ABC::env('INDEX_FILE') . $url;
            }

            BannerStat::create(
                [
                    'banner_id' => $banner_id,
                    'type'      => 1,
                    'store_id'  => $this->config->get('config_store_id'),
                    'user_info' => [
                        'user_id'   => $this->customer?->getId(),
                        'user_ip'   => $this->request->getRemoteIP(),
                        'user_host' => $this->request->server['REMOTE_HOST'],
                        'page_rt'   => $this->request->get['page_rt'],
                    ]
                ]
            );
        }
        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
        //go to URL
        abc_redirect($url);
    }
}