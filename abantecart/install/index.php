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

namespace abc;

use abc\core\ABC;
use abc\core\engine\APage;
use abc\core\engine\ARouter;
use abc\core\engine\Registry;
use abc\core\lib\ADebug;
use abc\core\lib\ADocument;
use abc\core\lib\AException;
use abc\core\lib\ARequest;
use abc\core\lib\AResponse;

define('DS', DIRECTORY_SEPARATOR);
if (version_compare(phpversion(), '7.0.0', '<') == true) {
    exit("Oops... php v7.0.0+  Required for AbanteCart to work properly!\n");
}

if (!is_writable(__DIR__.'/../abc/system/logs')) {
    exit('Please make directory </br></br>'.realpath(__DIR__.'/../abc/system/logs')
        .'</br></br>writable for PHP to proceed');
}

require __DIR__.'/../abc/core/abc.php';
// Windows IIS Compatibility
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    ABC::env('IS_WINDOWS', true);
}
ABC::env('APP_CHARSET', 'UTF-8');
ABC::env('INSTALL', true);
ABC::env('IS_ADMIN', true);
ABC::env('INDEX_FILE', basename(__FILE__));
//load default environment
new ABC();

require 'core/init.php';
$registry = Registry::getInstance();
ADebug::checkpoint('init end');

$request = new ARequest();
$registry->set('request', $request);
//Route to request process
$router = new ARouter($registry);
$registry->set('router', $router);

//Show cache stats if debugging
if ($registry->get('config')->get('config_debug')) {
    ADebug::variable('Cache statistics: ',
        $registry->get('cache')->stats()."\n");
}

ADebug::checkpoint('app end');

//display debug info
if ($router->getRequestType() == 'page') {
    ADebug::display();
}

try {
// Response
    $response = new AResponse();
    $response->addHeader('Content-Type: text/html; charset=utf-8');
    $registry->set('response', $response);

// Document
    $document = new ADocument();
    $document->setBase(ABC::env('HTTPS_SERVER'));
    $registry->set('document', $document);

// Page Controller 
    $page_controller = new APage($registry);

// Router
    if (!empty($request->get['rt'])) {
        $dispatch = $request->get['rt'];
    } else {
        $dispatch = 'license';
    }

    $page_controller->build('pages/'.$dispatch);

// Output
    $response->output();
} catch (AException $e) {
    ac_exception_handler($e);
}