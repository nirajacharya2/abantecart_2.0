<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2022 Belavier Commerce LLC

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
use abc\core\lib\contracts\AssetPublisherDriverInterface;
use H;

class AssetPublisherCopy implements AssetPublisherDriverInterface
{
    public $errors = [];

    public function publishFiles($files = [])
    {

        //core files first
        if ($files['core']) {
            $result = $this->publishCoreAssets($files['core']);
            if (!$result) {
                return false;
            }
        }
        //extensions files
        if ($files['extensions']) {
            $result = $this->publishExtensionsAssets($files['extensions']);

            if (!$result) {
                return false;
            }
        }
        //vendors files
        if ($files['vendors']) {
            $result = $this->publishVendorAssets($files['vendors']);
            if (!$result) {
                return false;
            }
        }

        return true;
    }

    protected function publishCoreAssets($file_list)
    {
        if (!$file_list || !is_array($file_list)) {
            return false;
        }
        $src_dir = ABC::env('DIR_TEMPLATES');
        $dest_dir = ABC::env('DIR_PUBLIC').ABC::env('DIRNAME_TEMPLATES');
        return $this->processTemplateAssets($file_list, $src_dir, $dest_dir);
    }

    protected function publishExtensionsAssets($extensions_files_list)
    {
        if (!$extensions_files_list || !is_array($extensions_files_list)) {
            return false;
        }
        foreach ($extensions_files_list as $extension => $file_list) {
            if (!$file_list) {
                continue;
            }

            $src_dir = ABC::env('DIR_APP_EXTENSIONS')
                .$extension.DS
                .ABC::env('DIRNAME_TEMPLATES');

            $dst_dir = ABC::env('DIR_PUBLIC')
                .ABC::env('DIRNAME_EXTENSIONS')
                .$extension.DS
                .ABC::env('DIRNAME_TEMPLATES');
            $result = $this->processTemplateAssets($file_list, $src_dir, $dst_dir);

            if ($result === false) {
                return false;
            }
        }
        return true;
    }

    protected function publishVendorAssets($file_list)
    {
        if (!$file_list || !is_array($file_list)) {
            return false;
        }
        $src_dir = ABC::env('DIR_VENDOR').'assets'.DS;
        $dest_dir = ABC::env('DIR_PUBLIC').'vendor'.DS;
        return $this->processTemplateAssets($file_list, $src_dir, $dest_dir);
    }

    protected function processTemplateAssets($file_list, $src_dir, $dest_dir)
    {
        $commonResult = true;
        if (!$file_list || !is_array($file_list)) {
            return false;
        }
        foreach ($file_list as $template => $list) {
            //remove previous temp-folders before copying
            $live_dir = $dest_dir.$template;
            //unique temporary directory name
            $uid_new = uniqid('apn_');
            //unique old directory name
            $uid_old = uniqid('apo_');
            //use abc/system/temp directory during copying
            $tmpDir = ABC::env('DIR_SYSTEM').'temp'.DS;
            if (!is_dir($tmpDir)) {
                @mkdir($tmpDir, 0775);
            }

            if (!is_writable($tmpDir)) {
                $this->errors[] = __CLASS__ . ': Temporary directory ' . $tmpDir . ' is not writable for php!';
                return false;
            }

            $new_temp_dir = $tmpDir . $uid_new;
            $backup_dir = $tmpDir . $uid_old;

            //then copy all asset files of template to temporary directory
            foreach ($list as $rel_file) {
                $res = H::CopyFileRelative(
                    $rel_file,
                    $src_dir.$template.DS,
                    $new_temp_dir.DS
                );

                if (!$res['result']) {
                    $commonResult = false;
                    $this->errors[] = __CLASS__.': '.$res['message'];
                }
            }

            //if all fine - rename of temporary directory
            if (!$this->errors) {
                //if live assets presents - rename it
                if (is_dir($live_dir)) {
                    $result = rename($live_dir, $backup_dir);
                    if (!$result) {
                        $this->errors[] = __CLASS__ . ': Cannot backup live directory '
                            . $live_dir . ' to ' . $backup_dir . ' before publishing';
                        //debug details
                        $this->errors[] = 'Is directory ' . $live_dir . ' readable? : ' . var_export(is_readable($live_dir), true);
                        $this->errors[] = 'Is directory ' . dirname($backup_dir) . ' writable? : ' . var_export(is_writable(dirname($backup_dir)), true);

                        //return false;
                    }
                }

                //check parent directory before rename
                $parent_dir = dirname($live_dir);
                if (!is_dir($parent_dir)) {
                    $results = H::MakeNestedDirs($parent_dir);
                    if (!$results['result']) {
                        $this->errors[] = $results['message'];
                    }
                }
                //try to move to production
                if (!@rename($new_temp_dir, $live_dir)) {
                    $this->errors[] = __CLASS__ . ': Cannot rename temporary directory '
                        . $new_temp_dir . ' to live ' . $live_dir;
                    //revert old assets
                    @rename($backup_dir, $live_dir);
                    return false;
                } else {
                    //if all fine - clean old silently
                    H::RemoveDirRecursively($backup_dir);
                }
            }
        }
        return $commonResult;
    }
}