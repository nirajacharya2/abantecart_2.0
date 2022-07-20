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
use abc\core\lib\AWarning;
use H;

class ModelToolImage extends Model
{
    /**
     * @param string $filename
     * @param int $width
     * @param int $height
     *
     * @return null|string
     * @throws \ReflectionException
     */
    function resize($filename, $width, $height)
    {
        $orig_image_filepath = is_file(ABC::env('DIR_IMAGES').$filename) ? ABC::env('DIR_IMAGES').$filename : '';
        $orig_image_filepath = $orig_image_filepath == '' && is_file(ABC::env('DIR_RESOURCES').'image/'.$filename)
            ? ABC::env('DIR_RESOURCES').'image/'.$filename
            : $orig_image_filepath;

        $info = pathinfo($filename);
        $extension = $info['extension'];
        if (in_array($extension, ['ico', 'svg', 'svgz'])) {
            $new_image = $filename;
        } else {
            $new_image = 'thumbnails/'
                .substr($filename, 0, strrpos($filename, '.'))
                .'-'.$width.'x'.$height.'.'.$extension;
            if (!H::check_resize_image($orig_image_filepath, $new_image, $width, $height,
                $this->config->get('config_image_quality'))) {
                $err = new AWarning('Resize image error. File: '.$orig_image_filepath
                    .'. Try to increase memory limit for PHP or decrease image size.');
                $err->toLog()->toDebug();
                return false;
            }
        }

        if (ABC::env('HTTPS')) {
            return ABC::env('HTTPS_IMAGE').$new_image;
        } else {
            return ABC::env('HTTP_IMAGE').$new_image;
        }
    }
}
