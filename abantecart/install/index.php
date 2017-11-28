<?php
/*
------------------------------------------------------------------------------
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
------------------------------------------------------------------------------  
*/
namespace abantecart\install;
// Real path (operating system web root) to the directory where abantecart is installed
use abc\ABC;
use abc\core\engine\APage;
use abc\lib\ADB;
use abc\lib\ADebug;
use abc\lib\ADocument;
use abc\lib\AException;

$root_path = dirname(__FILE__);

if (ABC::env('IS_WINDOWS') === true) {
		$root_path = str_replace('\\', '/', $root_path);
}
ABC::env('DIR_ROOT', $root_path);
// Detect https
if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == '1')) {
	ABC::env('HTTPS', true);
} elseif (isset($_SERVER['HTTP_X_FORWARDED_SERVER']) && ($_SERVER['HTTP_X_FORWARDED_SERVER'] == 'secure' || $_SERVER['HTTP_X_FORWARDED_SERVER'] == 'ssl')) {
	ABC::env('HTTPS', true);
} elseif (isset($_SERVER['SCRIPT_URI']) && (substr($_SERVER['SCRIPT_URI'], 0, 5) == 'https')) {
	ABC::env('HTTPS', true);
} elseif (isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], ':443') !== false)) {
	ABC::env('HTTPS', true);
}else{
	ABC::env('HTTPS', false);
}

// HTTP
ABC::env('HTTP_SERVER', (ABC::env('HTTPS') ? 'https' : 'http').'://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/.\\') . '/');
ABC::env('HTTP_ABANTECART', (ABC::env('HTTPS') ? 'https' : 'http').'://' . $_SERVER['HTTP_HOST'] . rtrim(rtrim(dirname($_SERVER['PHP_SELF']), 'install'), '/.\\'). '/');

// DIR
ABC::env('DIR_ROOT', str_replace('\'', '/', realpath(dirname(dirname(__FILE__)))) . '/');
ABC::env('DIR_APP', str_replace('\'', '/', realpath(dirname(__FILE__))) . '/');
ABC::env('DIR_CORE', str_replace('\'', '/', realpath(dirname(__FILE__) . '/../')) . '/core/');
ABC::env('DIR_SYSTEM', str_replace('\'', '/', realpath(dirname(__FILE__) . '/../')) . '/system/');
ABC::env('DIR_CACHE', str_replace('\'', '/', realpath(dirname(__FILE__) . '/../')) . '/system/cache/');
ABC::env('DIR_LOGS', str_replace('\'', '/', realpath(dirname(__FILE__) . '/../')) . '/system/logs/');
ABC::env('DIR_ABANTECART', str_replace('\'', '/', realpath(ABC::env('DIR_APP') . '../')) . '/');
ABC::env('DIR_STOREFRONT', ABC::env('DIR_ABANTECART') . '/storefront/');
ABC::env('DIR_TEMPLATE', ABC::env('DIR_APP') . 'view/template/');
ABC::env('INSTALL', 'true');
// Relative paths and directories
ABC::env('RDIR_TEMPLATE',  'view/');

// Startup with local init
require_once('init.php');

//Check if cart is already installed
if (file_exists(ABC::env('DIR_SYSTEM') . 'config.php')){
	require_once(ABC::env('DIR_SYSTEM') . 'config.php');
}

$data_exist = false;
if ( ABC::env('DB_HOSTNAME') ) {
	$db = new ADB(array(
					'driver' => ABC::env('DB_DRIVER'),
					'host' => ABC::env('DB_HOSTNAME'),
					'username' => ABC::env('DB_USERNAME'),
					'password' => ABC::env('DB_PASSWORD'),
					'database' => ABC::env('DB_DATABASE'),
					'prefix'   => ABC::env('DB_PREFIX'),
					'charset'  => ABC::env('DB_CHARSET'),
					'collation'=> ABC::env('DB_COLLATION'),
				));
    $r = $db->query("SELECT * FROM ".$this->db->prefix()."settings");
    $data_exist = $r->num_rows;
} else {
    unset($session->data['finish']);
}

if ( $data_exist && !isset($session->data['finish']) ) {
    session_destroy();
    header('Location: ../');
}

if ( isset($session->data['finish']) && $session->data['finish'] == 'true' ) {
    $request->get['rt'] = 'finish';
}

try {

// Document
$document = new ADocument();
$document->setBase( ABC::env('HTTP_SERVER') );
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
}
catch (AException $e) {
    ac_exception_handler($e);
}

//display debug info
ADebug::display();
