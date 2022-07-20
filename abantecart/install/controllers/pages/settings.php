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

/**
 * Class ControllerPagesSettings
 *
 * @property \install\models\ModelInstall $model_install
 */
class ControllerPagesSettings extends AController
{
    private $error = [];

    public function main()
    {
        //check is cart already installed
        if(is_file(ABC::env('DIR_CONFIG').'enabled.config.php')){
            abc_redirect(ABC::env('HTTPS_SERVER').'index.php?rt=finish');
        }

        $template_data = [];
        if ($this->request->is_POST() && ($this->validate())) {
            abc_redirect(ABC::env('HTTPS_SERVER').'index.php?rt=install');
        }

        if (isset($this->error['warning'])) {
            $template_data['error_warning'] = $this->error['warning'];
        } else {
            $template_data['error_warning'] = '';
        }


        //try to disable opcache first (see details here http://php.net/manual/en/opcache.configuration.php#ini.opcache.enable)
        @ini_set('opcache.enable',0);
        //show warning if still not disabled
        if (ini_get('opcache.enable')) {
            if ($template_data['error_warning']) {
                $template_data['error_warning'] .= '<br>';
            }
            $template_data['error_warning'] .= 'Warning: Your server have opcache php module enabled. Please disable it before installation!';
        }
        @ini_set('apc.enabled',0);
        //show warning if still not disabled
        if (ini_get('apc.enabled')) {
            if ($template_data['error_warning']) {
                $template_data['error_warning'] .= '<br>';
            }
            $template_data['error_warning'] .= 'Warning: Your server have APC (Alternative PHP Cache) php module enabled. Please disable it before installation!';
        }

        $template_data['action'] = ABC::env('HTTPS_SERVER').'index.php?rt=settings';

        $template_data['php_ini'] = [
            'PHP Version' => [
                                'current' => phpversion(),
                                'required'=> ABC::env('MIN_PHP_VERSION').' or above',
                                'status'=> (!version_compare(phpversion(), ABC::env('MIN_PHP_VERSION'), '<') ? true : false)
                             ],
            'Magic Quotes GPC' => [
                                'current' => (ini_get('magic_quotes_gpc') ? 'On' : 'Off'),
                                'required'=> 'Off',
                                'status'=> (!ini_get('magic_quotes_gpc') ? true : false)
                             ],
            'File Uploads' => [
                                'current' => (ini_get('file_uploads') ? 'On' : 'Off'),
                                'required'=> 'On',
                                'status'=> (ini_get('file_uploads') ? true : false)
                             ],
            'Session Auto Start' => [
                                'current' => (ini_get('session_auto_start') ? 'On' : 'Off'),
                                'required'=> 'Off',
                                'status'=> (!ini_get('session_auto_start') ? true : false)
                             ],
            'Output Buffering' => [
                                'current' => (ini_get('output_buffering') ? 'On' : 'Off'),
                                'required'=> 'On',
                                'status'=> (ini_get('output_buffering') ? true : false)
                             ],

        ];

        $template_data['directories'] = [
            ABC::env('DIR_CONFIG'),
            ABC::env('DIR_SYSTEM'),
            ABC::env('CACHE')['stores']['file']['path'],
            ABC::env('DIR_LOGS'),
            ABC::env('DIR_DOWNLOADS'),
            ABC::env('DIR_APP_EXTENSIONS'),
            ABC::env('DIR_PUBLIC'),
            ABC::env('DIR_RESOURCES'),
            ABC::env('DIR_IMAGES'),
            ABC::env('DIR_IMAGES').'thumbnails',
        ];

        $template_data['php_libs'] = [
            'MySQL' => (extension_loaded('mysql') || extension_loaded('mysqli') || extension_loaded('pdo_mysql') ? true : false),
            'GD' => (extension_loaded('gd') ? true : false),
            'CURL' => (extension_loaded('curl') ? true : false),
            'ZLIB' => (extension_loaded('zlib') ? true : false),
            'PHAR' => (extension_loaded('phar') ? true : false),
            'MultiByte String (mbstring)' => (extension_loaded('mbstring') && function_exists('mb_internal_encoding') ? true : false),
            'OpenSSL' => (extension_loaded('openssl') ? true : false),
        ];

        $this->addChild('common/header', 'header', 'common/header.tpl');
        $this->addChild('common/footer', 'footer', 'common/footer.tpl');

        $this->view->assign('back', ABC::env('HTTPS_SERVER').'index.php?rt=license');
        $this->view->batchAssign($template_data);
        $this->processTemplate('pages/settings.tpl');
    }

    /**
     * @return bool
     * @throws \abc\core\lib\AException
     */
    public function validate()
    {
        $this->load->model('install');
        $result = $this->model_install->validateRequirements();
        if ( ! $result) {
            $this->error = $this->model_install->error;
        }
        return $result;
    }
}
