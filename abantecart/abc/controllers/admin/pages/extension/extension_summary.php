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

namespace abc\controllers\admin;

use abc\core\ABC;
use abc\core\engine\AController;
use H;

class ControllerPagesExtensionExtensionSummary extends AController
{
    public $data = [];

    public function main()
    {
        //Load input arguments for gid settings
        $this->data = func_get_arg(0);
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('extension/extensions');
        $extension = $this->request->get['extension'];
        if ($extension && !$this->data['extension_info']) {
            $this->data['extension_info'] = $this->extensions->getExtensionInfo($extension);
        }

        $icon_ext_img_url = ABC::env('HTTPS_EXT').$extension.'/images/icon.png';
        $icon_ext_dir = ABC::env('DIR_EXT').$extension.'/images/icon.png';
        $icon = (is_file($icon_ext_dir) ? $icon_ext_img_url : ABC::env('RDIR_ASSETS').'images/default_extension.png');

        $this->data['extension_info']['icon'] = $icon;
        $this->data['extension_info']['name'] = $this->language->get($extension.'_name');

        $datetime_format = $this->language->get('date_format_short').' '.$this->language->get('time_format');

        if ($this->data['extension_info']['date_installed']) {
            $this->data['extension_info']['installed'] =
                H::dateISO2Display($this->data['extension_info']['date_installed'], $datetime_format);
        }
        if ($this->data['extension_info']['date_added']) {
            $this->data['extension_info']['date_added'] =
                H::dateISO2Display($this->data['extension_info']['date_added'], $datetime_format);
        }
        $updates = $this->cache->get('extensions.updates');

        // if update available
        if (is_array($updates) && in_array($extension, array_keys($updates))) {
            if ($updates[$extension]['installation_key']) {
                $update_now_url = $this->html->getSecureURL('tool/package_installer',
                    '&extension_key='.$updates[$extension]['installation_key']);
            } else {
                $update_now_url = $updates[$extension]['url'];
            }
            $this->data['upgrade_button'] = $this->html->buildElement(
                [
                    'type' => 'button',
                    'name' => 'btn_upgrade',
                    'id'   => 'upgradenow',
                    'href' => $update_now_url,
                    'text' => $this->language->get('button_upgrade'),
                ]);
        }

        $this->data['extension_info']['license'] = $this->data['extension_info']['license_key'];
        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/extension/extension_summary.tpl');

        //update controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }
}