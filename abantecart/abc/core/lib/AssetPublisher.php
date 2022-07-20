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

namespace abc\core\lib;

use abc\core\ABC;
use abc\core\engine\ALoader;
use abc\core\engine\ExtensionsApi;
use abc\core\engine\Registry;
use abc\core\lib\contracts\AssetPublisherDriverInterface;
use H;

/**
 * Class AAssetPublisher
 *
 * @property ADB $db
 * @property ALanguageManager $language
 * @property AConfig $config
 * @property ASession $session
 * @property ALoader $load
 * @property ExtensionsApi $extensions
 *
 */
class AssetPublisher
{
    /**
     * @var Registry
     */
    protected $registry;
    public $errors = [];

    public function __construct()
    {
        // forbid for non admin calls
        if (!ABC::env('IS_ADMIN')) {
            throw new AException('Error: permission denied. ', AC_ERR_LOAD);
        }
        $this->registry = Registry::getInstance();

        if (!$this->validate()) {
            throw new AException('Error: '.implode("\n", $this->errors), AC_ERR_LOAD);
        }
    }

    protected function validate()
    {
        $vars = [
            'DIR_TEMPLATES',
            'DIR_APP_EXTENSIONS',
            'DIR_PUBLIC',
            'DIR_VENDOR',
            'DIR_APP_EXTENSIONS',
        ];
        foreach ($vars as $name) {
            if (!ABC::env($name)) {
                $this->errors[] = __CLASS__.': Empty environment variable value: '.$name;
            }
        }
        return $this->errors ? false : true;
    }

    public function __get($key)
    {
        return $this->registry->get($key);
    }

    public function publish($source = 'all', $options = [])
    {
        $source = !$source ? 'all' : (string)$source;
        $files_list = $this->getSourceAssetsFiles($source, $options);
        if (!$files_list) {
            return true;
        }
        //try to get driver class name from config
        $driver = null;
        if (ABC::env('asset_publisher_driver')
            && strtolower(ABC::env('asset_publisher_driver')) != 'assetpublishercopy') {
            $this->load->library(strtolower(ABC::env('asset_publisher_driver')));
            if (class_exists(ABC::env('asset_publisher_driver'))) {
                $namespace = "\abc\core\lib\\";
                $driver = new $namespace.${ABC::env('asset_publisher_driver')}();
            } else {
                throw new AException(
                    'Missing Library Class'
                    ." Asset publisher driver class ".ABC::env('asset_publisher_driver')." not found!",
                    AC_ERR_CLASS_CLASS_NOT_EXIST
                );
            }
        }
        //use default
        if (!is_object($driver)) {
            $driver = new AssetPublisherCopy();
        }

        if(!$driver instanceof AssetPublisherDriverInterface){
            throw new \Exception(get_class($driver).' in not instance of '.AssetPublisherDriverInterface::class);
        }

        $result = $driver->publishFiles($files_list);
        if (!$result) {
            $this->errors = (array)$this->errors + (array)$driver->errors;
        }
        return $result;
    }

    /**
     * @param string $source - can be 'core':
     *                         - to publish only assets from abc/templates directory,
     *                         - 'extensions' - only assets from abc/extensions directory,
     *                         - 'vendors' - only assets from vendor directory,
     *                         '{extension_text_id}' - to publish only extension assets,
     *                         and 'all' to publish all
     * @param array $filter
     *
     * @return array
     */
    public function getSourceAssetsFiles($source = 'all', $filter = [])
    {
        $core_assets = $extensions_assets = $vendors_assets = [];
        if (!in_array($source, ['all', 'core', 'extensions', 'vendors'])) {
            $filter = (string)$source;
        }

        if (in_array($source, ['all', 'core'])) {
            $template_dirs = array_map('basename', (array)glob(ABC::env('DIR_TEMPLATES').'*', GLOB_ONLYDIR));
            foreach ($template_dirs as $template) {
                $dirs = glob(
                    ABC::env('DIR_TEMPLATES').$template.DS.'*'.DS.'assets',
                    GLOB_ONLYDIR
                );
                foreach ($dirs as $dir) {
                    $files = H::getFilesInDir($dir);
                    foreach ($files as $file) {
                        $core_assets[$template][] = H::getRelativePath(
                            ABC::env('DIR_TEMPLATES').$template.DS,
                            $file
                        );
                    }
                }
            }
        }

        if ($source == 'all' || ($source == 'extensions' && !isset($filter['extension']))) {
            //Note: get only enabled extensions for publishing
            $extensions_api = $this->extensions;

            if (!is_callable($extensions_api)) {
                // Extensions api
                $extensions_api = new ExtensionsApi();
                $extensions_api->loadAvailableExtensions();
                $this->registry->set('extensions', $extensions_api);
            }

            $enabled_extensions = $this->extensions->getEnabledExtensions();

            foreach ($enabled_extensions as $extension) {
                $extensions_assets[$extension] = $this->getExtensionAssets($extension);
            }
        } elseif (isset($filter['extension']) && $filter['extension']) {
            //when needs to publish assets of extension
            $extensions_assets[$filter['extension']] = $this->getExtensionAssets($filter['extension']);
        }
        //when publish only vendors assets
        if ($source == 'all' || $source == 'vendors') {
            if (isset($filter['package']) && $filter['package']) {
                list($vendor_name, $package_name) = explode(':', $filter['package']);
                //only one vendors package
                $dirs = [
                    ABC::env('DIR_VENDOR').'assets'.DS
                    .$vendor_name.DS.$package_name,
                ];
            } else {
                //all vendors packages
                $dirs = glob(ABC::env('DIR_VENDOR').'assets'.DS.'*', GLOB_ONLYDIR);
            }
            foreach ($dirs as $dir) {
                $files = H::getFilesInDir($dir);
                foreach ($files as $file) {
                    $rel_file = H::getRelativePath(
                        ABC::env('DIR_VENDOR').'assets'.DS,
                        $file
                    );
                    $vendor_name = explode(DS, $rel_file);
                    $vendor_name = $vendor_name[0];
                    $rel_file = H::getRelativePath(
                        ABC::env('DIR_VENDOR').'assets'.DS.$vendor_name.DS,
                        $file
                    );
                    $vendors_assets[$vendor_name][] = $rel_file;
                }
            }
        }

        $output = [
            'core'       => $core_assets,
            'extensions' => $extensions_assets,
            'vendors'    => $vendors_assets,
        ];
        return $output;
    }

    /**
     * @param $extension_name
     *
     * @return array
     */
    protected function getExtensionAssets($extension_name)
    {
        if (!$extension_name) {
            return [];
        }
        $extensions_assets = [];
        $template_dirs = array_map('basename', (array)glob(ABC::env('DIR_APP_EXTENSIONS')
            .$extension_name.'/templates/*', GLOB_ONLYDIR));
        foreach ($template_dirs as $template) {
            if ($template === '') {
                continue;
            }
            $dir_pattern = ABC::env('DIR_APP_EXTENSIONS').$extension_name.'/templates/'.$template.'/*/assets';
            $dirs = glob($dir_pattern, GLOB_ONLYDIR);
            foreach ($dirs as $dir) {
                $files = H::getFilesInDir($dir);
                foreach ($files as $file) {
                    $extensions_assets[$template][] = H::getRelativePath(
                        ABC::env('DIR_APP_EXTENSIONS')
                        .$extension_name.DS
                        .ABC::env('DIRNAME_TEMPLATES').$template.'/',
                        $file
                    );
                }
            }
        }
        return $extensions_assets;
    }

}
