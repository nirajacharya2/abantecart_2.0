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

namespace install\controllers;

use abc\core\ABC;
use abc\core\engine\AController;

class ControllerPagesFinish extends AController
{
    public function main()
    {
        if ( ! ABC::env('DATABASES')) {
            header('Location: index.php?rt=license');
            exit;
        }

        $this->session->data['finish'] = 'true';
        // prevent reinstall bugs with ant
        unset($this->session->data ['ant_messages']);
        $public_url = $this->_parent_url_dir(ABC::env('HTTPS_SERVER')).'public/';
        $this->view->assign('storefront_url', $public_url.'index.php');
        $this->view->assign('admin_url', $public_url.'index.php?s='.ABC::env('ADMIN_SECRET'));

        $message = "Keep your e-commerce secure! <br /> Delete directory ".ABC::env('DIR_INSTALL')." install from your AbanteCart installation!";
        $this->view->assign('message', $message);

        $this->addChild('common/header', 'header', 'common/header.tpl');
        $this->addChild('common/footer', 'footer', 'common/footer.tpl');

        $this->processTemplate('pages/finish.tpl');
    }

    /**
     * Function returns parent directory of URL
     * @param $url
     *
     * @return bool|string
     */
    protected function _parent_url_dir($url) {
        // note: parent of "/" is "/" and parent of "http://example.com" is "http://example.com/"
        // remove filename and query
        $url = $this->_current_url_dir($url);
        // get parent
        $len = strlen($url);
        return $this->_current_url_dir(substr($url, 0, $len && $url[$len - 1 ] == '/' ? -1 : $len));
    }

    protected function _current_url_dir($url) {
        // note: anything without a scheme ("example.com", "example.com:80/", etc.) is a folder
        // remove query (protection against "?url=http://example.com/")
        if ($first_query = strpos($url, '?')) $url = substr($url, 0, $first_query);
        // remove fragment (protection against "#http://example.com/")
        if ($first_fragment = strpos($url, '#')) $url = substr($url, 0, $first_fragment);
        // folder only
        $last_slash = strrpos($url, '/');
        if (!$last_slash) {
            return '/';
        }
        // add ending slash to "http://example.com"
        if (($first_colon = strpos($url, '://')) !== false && $first_colon + 2 == $last_slash) {
            return $url . '/';
        }
        return substr($url, 0, $last_slash + 1);
    }

}