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
namespace abc\lib;
use abc\ABC;
use abc\core\engine\ALoader;
use abc\core\engine\ExtensionsApi;
use abc\core\helper\AHelperUtils;
use abc\core\engine\Registry;

if (!class_exists('abc\ABC')) {
    header('Location: static_pages/?forbidden='.basename(__FILE__));
}

/**
 * Class AAssetPublisher
 * @property ADB $db
 * @property ALanguageManager $language
 * @property AConfig $config
 * @property ASession $session
 * @property ACache $cache
 * @property ALoader $load
 * @property ExtensionsApi $extensions
 *
 */
class AAssetPublisher{
    /**
     * @var Registry
     */
    protected $registry;
    public $errors = [];

    public function __construct(){
        // forbid for non admin calls
        if (!ABC::env('IS_ADMIN')){
            throw new AException (AC_ERR_LOAD, 'Error: permission denied. ');
        }
        $this->registry = Registry::getInstance();

        if(!$this->validate()){
            throw new AException (AC_ERR_LOAD, 'Error: '.implode("\n",$this->errors));
        }
    }

    protected function validate(){
        $vars = [
            'DIR_TEMPLATES',
            'DIR_APP_EXTENSIONS',
            'DIR_PUBLIC',
            'DIR_VENDOR',
            'DIR_APP_EXTENSIONS'
            ];
        foreach($vars as $name) {
            if ( !ABC::env($name)) {
                $this->errors[] = 'Empty environment variable value: '.$name;
            }
        }
        return $this->errors ? false : true;
    }

    public function __get($key){
        return $this->registry->get($key);
    }


    public function publish($source = 'all'){
        $source = !$source ? 'all' : (string)$source;
        $files_list = $this->getSourceAssetsFiles($source);
        if(!$files_list){
            return true;
        }
        //try to get driver class name from config
        $driver = null;
        if( ABC::env( 'asset_publisher_driver' ) && strtolower(ABC::env( 'asset_publisher_driver' )) != 'assetpublishercopy' ){
            $this->load->library(strtolower(ABC::env( 'asset_publisher_driver' )));
            if(class_exists(ABC::env( 'asset_publisher_driver' ))) {
                $namespace = "\abc\lib\\";
                $driver = new $namespace.${ABC::env('asset_publisher_driver')}();
            }
            ADebug::error('Missing Library Class', AC_ERR_CLASS_CLASS_NOT_EXIST, "Asset publisher driver class ".ABC::env('asset_publisher_driver')." not found!");
        }
        //use default
        if(!is_object($driver)){
            $driver = new AssetPublisherCopy();
        }

        $result = $driver->publishFiles($files_list);
        if(!$result){
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
     * @return array
     */
    public function getSourceAssetsFiles($source = 'all'){
        $core_assets = $extensions_assets = $vendors_assets = [];
        $extension_name = '';
        if( !in_array($source, array('all', 'core', 'extensions', 'vendors'))){
            $extension_name = (string)$source;
        }

        if( in_array($source, array('all', 'core')) ){
            $template_dirs = array_map('basename', (array)glob(ABC::env('DIR_TEMPLATES').'*', GLOB_ONLYDIR));
            foreach ($template_dirs as $template) {
                $dirs = glob(ABC::env('DIR_TEMPLATES').$template.'/*/assets',
                    GLOB_ONLYDIR);
                foreach ($dirs as $dir) {
                    $files = AHelperUtils::getFilesInDir($dir);
                    foreach ($files as $file) {
                        $core_assets[$template][]
                            = AHelperUtils::getRelativePath(ABC::env('DIR_TEMPLATES')
                            .$template.'/', $file);
                    }
                }
            }
        }

        if( $source == 'all' || $source == 'extensions') {
            //Note: get only enabled extensions for publishing
            $extensions_api = $this->extensions;
            if(!is_callable($extensions_api)){
                // Extensions api
                $extensions_api = new ExtensionsApi();
                $extensions_api->loadAvailableExtensions();
                $this->registry->set('extensions', $extensions_api);
            }
            $enabled_extensions = $this->extensions->getEnabledExtensions();
            foreach($enabled_extensions as $extension){
                $extensions_assets[$extension] = $this->_get_extension_assets($extension);
            }
        }
        //when needs to publish assets only for given extension
        elseif( $extension_name ){
            $extensions_assets[$extension_name] = $this->_get_extension_assets($extension_name);
        }
        //when publish only vendors assets
        if( $source == 'all' || $source == 'vendors' ){
            $dirs = glob(ABC::env('DIR_VENDOR').'assets/*',GLOB_ONLYDIR);
            foreach ($dirs as $dir) {
                $files = AHelperUtils::getFilesInDir($dir);
                foreach ($files as $file) {
                    $rel_file = AHelperUtils::getRelativePath(ABC::env('DIR_VENDOR').'assets'.DIRECTORY_SEPARATOR, $file);
                    $vendor_name = explode(DIRECTORY_SEPARATOR,$rel_file);
                    $vendor_name = $vendor_name[0];
                    $rel_file = AHelperUtils::getRelativePath(ABC::env('DIR_VENDOR').'assets'.DIRECTORY_SEPARATOR.$vendor_name.DIRECTORY_SEPARATOR, $file);
                    $vendors_assets[$vendor_name][] = $rel_file;
                }
            }
        }

        $output = [
            'core' => $core_assets,
            'extensions' => $extensions_assets,
            'vendors' => $vendors_assets
        ];

        return $output;
    }

    /**
     * @param $extension_name
     *
     * @return array
     */
    protected function _get_extension_assets($extension_name){
        if(!$extension_name){
            return array();
        }
        $extensions_assets = [];
        $template_dirs = array_map('basename', (array)glob(ABC::env('DIR_APP_EXTENSIONS').$extension_name.'/templates/*',GLOB_ONLYDIR));
        foreach($template_dirs as $template) {
            if($template === ''){ continue; }
            $dirs = glob(ABC::env('DIR_APP_EXTENSIONS').$extension_name . '/templates/'.$template.'/*/assets', GLOB_ONLYDIR);
            foreach ($dirs as $dir) {
                $files = AHelperUtils::getFilesInDir($dir);
                foreach ($files as $file) {
                    $extensions_assets[$template][]= AHelperUtils::getRelativePath(
                        ABC::env('DIR_APP_EXTENSIONS')
                        .$extension_name.DIRECTORY_SEPARATOR
                        .ABC::env('DIRNAME_TEMPLATES')
                        .$template.'/',$file);
                }
            }
        }
        return $extensions_assets;
    }

}

class AssetPublisherCopy{
    public $errors = array();
    public function publishFiles($files = array()){

        //core files first
        if($files['core']){
            $result = $this->_publish_core_assets($files['core']);
            if(!$result){
                return false;
            }
        }
        //extensions files
        if($files['extensions']){
            $result = $this->_publish_extensions_assets($files['extensions']);
            if(!$result){
                return false;
            }
        }
        //vendors files
        if($files['vendors']){
            $result = $this->_publish_vendor_assets($files['vendors']);
            if(!$result){
                return false;
            }
        }

        return true;
    }

    protected function _publish_core_assets($file_list)
    {
        if ( ! $file_list || ! is_array($file_list)) {
            return false;
        }
        $src_dir = ABC::env('DIR_TEMPLATES');
        $dest_dir = ABC::env('DIR_PUBLIC').ABC::env('DIRNAME_TEMPLATES');
        return $this->_process_template_assets($file_list,$src_dir,$dest_dir);
    }

    protected function _publish_extensions_assets($extensions_files_list)
    {
        if ( ! $extensions_files_list || ! is_array($extensions_files_list)) {
            return false;
        }
        foreach($extensions_files_list as $extension=>$file_list) {

            $src_dir = ABC::env('DIR_APP_EXTENSIONS').$extension.DIRECTORY_SEPARATOR.ABC::env('DIRNAME_TEMPLATES');
            $dst_dir = ABC::env('DIR_PUBLIC')
                            .ABC::env('DIRNAME_EXTENSIONS')
                            .$extension.DIRECTORY_SEPARATOR
                            .ABC::env('DIRNAME_TEMPLATES');

            $result = $this->_process_template_assets($file_list,$src_dir,$dst_dir);

            if(!$result){
                return false;
            }
        }
        return true;
    }


    protected function _publish_vendor_assets($file_list)
    {
        if ( ! $file_list || ! is_array($file_list)) {
            return false;
        }
        $src_dir = ABC::env('DIR_VENDOR').'assets/';
        $dest_dir = ABC::env('DIR_PUBLIC').ABC::env('DIRNAME_VENDOR');
        return $this->_process_template_assets($file_list,$src_dir,$dest_dir);
    }

    protected function _process_template_assets($file_list, $src_dir, $dest_dir){
        if ( !$file_list || ! is_array($file_list)) {
            return false;
        }
        foreach ($file_list as $template => $list) {
            //remove previous temp-folders before copying
            $live_dir = $dest_dir.$template;
            $new_temp_dir = $dest_dir.$template.'_new';
            $old_temp_dir = $dest_dir.$template.'_trash';
            $temp_dirs = [$new_temp_dir, $old_temp_dir];
            foreach ($temp_dirs as $temp_dir) {
                if (is_dir($temp_dir)) {
                    $this->_remove_dir($temp_dir);
                    //if cannot to remove files - return false
                    if ($this->errors) {
                        return false;
                    }
                }
            }

            //then copy all asset files of template to temporary directory
            foreach ($list as $rel_file) {
                $this->_copy_asset_file(
                    $rel_file,
                    $src_dir.$template.DIRECTORY_SEPARATOR,
                    $new_temp_dir.DIRECTORY_SEPARATOR);
            }

            //if all fine - do renaming of temporary directory
            if ( ! $this->errors) {
                //if live assets presents - rename it
                if(is_dir($live_dir)){
                    $result = rename($live_dir, $old_temp_dir);
                }else{
                    $result = true;
                }

                if ($result) {
                    if (!rename($new_temp_dir, $live_dir)) {
                        $this->errors[] = 'Cannot to rename directory ' .$new_temp_dir.' to '.$template;
                        //revert old assets
                        rename($old_temp_dir, $live_dir);
                        return false;
                    }else{
                        //if all fine - clean old
                        $this->_remove_dir($old_temp_dir);
                    }
                    //if all fine - remove old live directory
                } else {
                    $this->errors[] = 'Cannot to rename directory ' .$live_dir.' to '.$template.'_trash';
                    return false;
                }
            }
        } //end foreach
        return true;
    }
/*
    protected function _process_vendor_assets($file_list, $src_dir, $dest_dir){
        if ( !$file_list || ! is_array($file_list)) {
            return false;
        }
        foreach ($file_list as $template => $list) {
            //remove previous temp-folders before copying
            $live_dir = $dest_dir.$template;
            $new_temp_dir = $dest_dir.$template.'_new';
            $old_temp_dir = $dest_dir.$template.'_trash';
            $temp_dirs = [$new_temp_dir, $old_temp_dir];
            foreach ($temp_dirs as $temp_dir) {
                if (is_dir($temp_dir)) {
                    $this->_remove_dir($temp_dir);
                    //if cannot to remove files - return false
                    if ($this->errors) {
                        return false;
                    }
                }
            }

            //then copy all asset files of template to temporary directory
            foreach ($list as $rel_file) {
                $this->_copy_asset_file(
                    $rel_file,
                    $src_dir.$template.DIRECTORY_SEPARATOR,
                    $new_temp_dir.DIRECTORY_SEPARATOR);
            }

            //if all fine - do renaming of temporary directory
            if ( ! $this->errors) {
                //if live assets presents - rename it
                if(is_dir($live_dir)){
                    $result = rename($live_dir, $old_temp_dir);
                }else{
                    $result = true;
                }

                if ($result) {
                    if (!rename($new_temp_dir, $live_dir)) {
                        $this->errors[] = 'Cannot to rename directory ' .$new_temp_dir.' to '.$template;
                        //revert old assets
                        rename($old_temp_dir, $live_dir);
                        return false;
                    }else{
                        //if all fine - clean old
                        $this->_remove_dir($old_temp_dir);
                    }
                    //if all fine - remove old live directory
                } else {
                    $this->errors[] = 'Cannot to rename directory ' .$live_dir.' to '.$template.'_trash';
                    return false;
                }
            }
        } //end foreach
        return true;
    }
*/
    protected function _copy_asset_file($rel_file, $src_dir, $dest_dir)
    {
        $src_file = $src_dir.$rel_file;
        $dest_file = $dest_dir.$rel_file;
        if(!is_file($src_file)){
            $this->errors[] = 'Error: asset file '.$src_file.' not found during copying';
            return false;
        }
        $result = true;

        //create nested dirs
        $file_dir = dirname($dest_file);
        if($file_dir!== ''){
            $result = $this->_make_dir_recursive($file_dir);
        }
        //copy file
        if( $result ){
            $result = copy($src_file,$dest_file);
            if(!$result){
                $this->errors[] = 'Error: asset file '.$src_file.' copying error.';
            }
            return $result;
        }
        return false;
    }

    protected function _make_dir_recursive($full_dir_name, $perms = 0755)
    {
        $dirs = explode('/', $full_dir_name);
        $dir='';
        $result = true;
        foreach ($dirs as $part) {
            $dir .= $part.'/';
            if ( !is_dir($dir) && strlen($dir) )
                $result = mkdir($dir, $perms);
                if(!$result){
                    $this->errors[] = 'Cannot to create directory '.$dir;
                    return false;
                }
        }
        return $result;
    }

    protected function _remove_dir($dir = ''){
        if($dir == '../' || $dir == '/' || $dir == './' ){
            return false;
        }

        if (is_dir($dir)){
            $objects = scandir($dir);
            foreach ($objects as $obj){
                if ($obj != "." && $obj != ".."){
                    @chmod($dir . "/" . $obj, 0777);
                    $err = is_dir($dir . "/" . $obj) ? $this->_remove_dir($dir . "/" . $obj) : unlink($dir . "/" . $obj);
                    if (!$err){
                        $error_text = "Error: Can't to delete file or directory: '" . $dir . "/" . $obj . "'.";
                        $this->errors[] = $error_text;
                        return false;
                    }
                }
            }
            reset($objects);
            $result = rmdir($dir);
            return $result;
        } else {
            return $dir;
        }
    }
}