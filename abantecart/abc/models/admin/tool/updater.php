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

namespace abc\models\admin;

use abc\core\ABC;
use abc\core\engine\Model;
use abc\core\lib\AConnect;
use abc\core\lib\AExtensionManager;

/**
 * Class ModelToolUpdater
 *
 * @property ModelToolMPAPI $model_tool_mp_api
 */
class ModelToolUpdater extends Model
{

    /**
     * error text array
     *
     * @var array
     */
    public $error = [];
    /**
     * size of data in bytes
     *
     * @var int
     */
    public $dataSize = 0;

    /**
     * this method checks for updates on remote server if date about updates absent in cache (cache expires about day)
     *
     * @param bool $force - sign to do request to mp-server forcibly
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \abc\core\lib\AException
     */
    public function check4Updates($force = false)
    {
        if (!$force) {
            $update_info = $this->cache->get('extensions.updates');
        } else {
            $update_info = null;
        }

        if ($update_info === null) {
            $update_info = $this->_getUpdateInfo();

            if ($update_info) {
                $this->cache->put('extensions.updates', $update_info);
            }
        }
    }

    private function getExtensionsList()
    {
        $e = new AExtensionManager();
        $extensions_list = $e->getExtensionsList();
        $list = [];
        $installed_extensions = $this->extensions->getInstalled('');
        if ($extensions_list->num_rows) {
            foreach ($extensions_list->rows as $extension) {
                //skip default
                if (strpos($extension['key'], 'default') !== false) {
                    continue;
                }
                // if extension is installed
                if (in_array($extension['key'], $installed_extensions)) {
                    $status =
                        $extension['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled');

                    $extension_name = trim($this->extensions->getExtensionName($extension['key']));
                    $list[$extension['key']] = [
                        'name'        => $extension_name,
                        'type'        => $extension['type'],
                        'category'    => $extension['category'],
                        'status'      => $status,
                        'license_key' => $extension['license_key'],
                        'version'     => $extension['version'],
                    ];
                }
            }
        }

        return $list;
    }

    /**
     * this method gets json-formatted response from remote server and write it to cache
     *
     * @return array
     * @throws \abc\core\lib\AException
     */
    private function _getUpdateInfo()
    {
        $installed = [];

        $el = $this->getExtensionsList();

        $this->load->model('tool/mp_api');
        $url = $this->model_tool_mp_api->getMPURL().'?rt=a/product/updates';
        $url .= "&store_id=".ABC::env('UNIQUE_ID');
        $url .= "&store_ip=".$_SERVER ['SERVER_ADDR'];
        $url .= "&store_url=".ABC::env('HTTP_SERVER');
        $url .= "&software_name=AbanteCart";
        $url .= "&software_version=".ABC::env('VERSION');
        $url .= "&language_code=".$this->language->getLanguageCode();
        foreach ($el as $key => $extension) {
            $url .= '&extensions['.$key.']='.$extension['version'];
            $installed[$key] = $extension['version'];
        }
        //do connect without any http-redirects
        $pack = new AConnect(true, true);
        $info = $pack->getData($url);

        // get array with updates information
        if (!$info) {
            return [];
        }

        //filter data
        $output = [];
        foreach ($info as $key => $versions) {
            foreach ($versions as $version => $version_info) {
                //skip not installed
                if (!isset($installed[$key])) {
                    continue 1;
                }
                //skip not supported by cart
                if (!$version_info['cart_versions'] || !in_array(ABC::env('VERSION'), $version_info['cart_versions'])) {
                    continue;
                }
                //skip old or current versions
                if (version_compare($installed[$key], $version, '>=')) {
                    continue;
                }
                //if we have 2 or more versions for cart version
                if (!isset($output[$key][$version])
                    || version_compare($installed[$key], $version, '<')) {
                    $version_info['version'] = $version;
                    $output[$key] = $version_info;
                }
            }
        }
        return $output;
    }
}
