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
namespace abc\models\storefront;
use abc\core\ABC;
use abc\core\engine\Model;
use abc\core\lib\AWarning;
use H;

class ModelToolImage extends Model{
    public $data = [];

    /**
     * @param $filename - relative file path
     * @param int $width - in pixels
     * @param int $height - in pixels
     * @param null $alias - alias filename for saving
     * @param string $mode - can be url or file.
     *
     * @return null|string - string is URL or abs file path
     * @throws \ReflectionException
     */
    public function resize($filename, $width = 0, $height = 0, $alias = null, $mode = 'url')
    {
        if (!is_file(ABC::env('DIR_IMAGES').$filename) && !is_file(ABC::env('DIR_RESOURCES').'image/'.$filename)) {
            return null;
        }

        $orig_image_filepath = is_file(ABC::env('DIR_IMAGES').$filename) ? ABC::env('DIR_IMAGES').$filename : '';
        $orig_image_filepath = $orig_image_filepath == ''
        && is_file(ABC::env('DIR_RESOURCES').'image/'.$filename) ? ABC::env('DIR_RESOURCES').'image/'
            .$filename : $orig_image_filepath;

        $info = pathinfo($filename);
        $extension = $info['extension'];

        $alias = !$alias ? $filename : dirname($filename).'/'.basename($alias);
        $new_image = 'thumbnails/'.substr($alias, 0, strrpos($alias, '.')).'-'.$width.'x'.$height.'.'.$extension;
        $new_image2x = '';

        if (!H::check_resize_image($orig_image_filepath, $new_image, $width, $height,
            $this->config->get('config_image_quality'))) {
            $err = new AWarning('Resize image error. File: '.$orig_image_filepath
                .'. Try to increase memory limit for PHP or decrease image size.');
            $err->toLog()->toDebug();
        }

        if ($this->config->get('config_retina_enable')) {
            //retina variant
            $new_image2x =
                'thumbnails/'.substr($alias, 0, strrpos($alias, '.')).'-'.$width.'x'.$height.'@2x.'.$extension;
            if (!H::check_resize_image($orig_image_filepath, $new_image2x, $width * 2, $height * 2,
                $this->config->get('config_image_quality'))) {
                $err = new AWarning('Resize image error. File: '.$orig_image_filepath
                    .'. Try to increase memory limit for PHP or decrease image size.');
                $err->toLog()->toDebug();
            }
        }

        if ($this->config->get('config_retina_enable') && isset($this->request->cookie['HTTP_IS_RETINA'])) {
            $new_image = $new_image2x;
        }

        //when need to get abs path of result
        if ($mode == 'path') {
            $http_path = ABC::env('DIR_IMAGES');
        } else {
            //use auto-path without protocol (AUTO_SERVER)
            $http_path = ABC::env('HTTPS_IMAGE');
        }
        return $http_path.$new_image;
    }
}