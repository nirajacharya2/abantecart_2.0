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
namespace abantecart\install;
// set default encoding for multibyte php mod
use abc\ABC;
use abc\core\engine\Registry;
use abc\lib\ADataEncryption;
use abc\lib\ADocument;
use abc\lib\AException;
use abc\lib\ALog;
use abc\lib\ASession;
use abc\lib\CSRFToken;

mb_internal_encoding(ABC::env('APP_CHARSET'));
ini_set('default_charset', 'utf-8');

// Detect if localhost is used.
if (!isset($_SERVER['HTTP_HOST'])) {
	$_SERVER['HTTP_HOST'] = 'localhost';
}

// Detect https
if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == '1')) {
	ABC::env('HTTPS', true);
} elseif (isset($_SERVER['HTTP_X_FORWARDED_SERVER']) && ($_SERVER['HTTP_X_FORWARDED_SERVER'] == 'secure' || $_SERVER['HTTP_X_FORWARDED_SERVER'] == 'ssl')) {
	ABC::env('HTTPS', true);
} elseif (isset($_SERVER['SCRIPT_URI']) && (substr($_SERVER['SCRIPT_URI'], 0, 5) == 'https')) {
	ABC::env('HTTPS', true);
} elseif (isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], ':443') !== false)) {
	ABC::env('HTTPS', true);
}

// Detect http host
if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
	ABC::env('REAL_HOST', $_SERVER['HTTP_X_FORWARDED_HOST']);
} else {
	ABC::env('REAL_HOST', $_SERVER['HTTP_HOST']);
}

//Set up common paths
ABC::env('DIR_APP_EXTENSIONS', ABC::env('DIR_APP') . 'extensions/');
ABC::env('DIR_SYSTEM', ABC::env('DIR_APP') . 'system/');
ABC::env('DIR_CORE', ABC::env('DIR_APP') . 'core/');
ABC::env('DIR_LIB', ABC::env('DIR_APP') . 'lib/');
ABC::env('DIR_IMAGE', ABC::env('DIR_ASSETS') . 'images/');
ABC::env('DIR_DOWNLOAD', ABC::env('DIR_APP') . 'download/');
ABC::env('DIR_CONFIG', ABC::env('DIR_APP') . 'config/');
ABC::env('DIR_CACHE', ABC::env('DIR_APP') . 'system/cache/');
ABC::env('DIR_LOGS', ABC::env('DIR_APP') . 'system/logs/');
ABC::env('DIR_VENDOR', dirname(dirname(__FILE__)) . '/vendor/');

// AbanteCart Version
include(ABC::env('DIR_APP').'core/init/version.php');

// Error Reporting
error_reporting(E_ALL);
require_once(ABC::env('DIR_LIB') . 'debug.php');
require_once(ABC::env('DIR_LIB') . 'exceptions.php');
require_once(ABC::env('DIR_LIB') . 'error.php');
require_once(ABC::env('DIR_LIB') . 'warning.php');

//define rt - route for application controller
if($_GET['rt']) {
	ABC::env('ROUTE', $_GET['rt']);
} else if($_POST['rt']){
	ABC::env('ROUTE', $_POST['rt']);
} else {
	ABC::env('ROUTE', 'index/home');
}

try{
	//set ini parameters for session
	ini_set('session.use_trans_sid', 'Off');
	ini_set('session.use_cookies', 'On');
	ini_set('session.cookie_httponly', 'On');

// Magic Quotes
	if (ini_get('magic_quotes_gpc')) {
		function clean($data){
			if (is_array($data)) {
				foreach ($data as $key => $value) {
					$data[clean($key)] = clean($value);
				}
			} else {
				$data = stripslashes($data);
			}
			return $data;
		}

		$_GET = clean($_GET);
		$_POST = clean($_POST);
		$_COOKIE = clean($_COOKIE);
	}

	if (!ini_get('date.timezone')) {
		date_default_timezone_set('UTC');
	}

	if (!isset($_SERVER['DOCUMENT_ROOT'])) {
		if (isset($_SERVER['SCRIPT_FILENAME'])) {
			$_SERVER['DOCUMENT_ROOT'] = str_replace('\\', '/',
					substr($_SERVER['SCRIPT_FILENAME'], 0, 0 - strlen($_SERVER['PHP_SELF'])));
		}
	}

	if (!isset($_SERVER['DOCUMENT_ROOT'])) {
		if (isset($_SERVER['PATH_TRANSLATED'])) {
			$_SERVER['DOCUMENT_ROOT'] = str_replace('\\', '/',
					substr(str_replace('\\\\', '\\', $_SERVER['PATH_TRANSLATED']), 0,
							0 - strlen($_SERVER['PHP_SELF'])));
		}
	}

	if (!isset($_SERVER['REQUEST_URI'])) {
		$_SERVER['REQUEST_URI'] = substr($_SERVER['PHP_SELF'], 1);
		if (isset($_SERVER['QUERY_STRING'])) {
			$_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
		}
	}

// relative paths for extensions
	ABC::env(
		array(
			'DIRNAME_APP' => 'abc/',
			'DIRNAME_ASSETS' => 'assets/',
			'DIRNAME_EXTENSIONS' => 'extensions/',
			'DIRNAME_CORE' => 'core/',
			'DIRNAME_STORE'=> 'storefront/',
			'DIRNAME_ADMIN' => 'admin/',
			'DIRNAME_IMAGES'=> 'images/',
			'DIRNAME_CONTROLLERS' => 'controllers/',
			'DIRNAME_LANGUAGES' => 'languages/',
			'DIRNAME_TEMPLATES', 'templates/',
			'DIRNAME_TEMPLATE' => 'template/'
		)
	);

	ABC::env('DIR_APP_EXT', ABC::env('DIR_APP') . ABC::env('DIRNAME_EXTENSIONS'));
	ABC::env('DIR_ASSETS_EXT', ABC::env('DIR_ASSETS') . ABC::env('DIRNAME_EXTENSIONS'));

	require_once(ABC::env('DIR_CORE').'init/base.php');
	$registry = Registry::getInstance();
	require_once(ABC::env('DIR_CORE').'init/admin.php');

	// Session
	$registry->set('session', new ASession('PHPSESSID_AC'));

// CSRF Token Class
	$registry->set('csrftoken', new CSRFToken());

// Log
	$registry->set('log', new ALog(ABC::env('DIR_LOGS')));

// Document
	$registry->set('document', new ADocument());

// AbanteCart Snapshot details
	$registry->set('snapshot', 'AbanteCart/' . ABC::env('VERSION') . ' ' . $_SERVER['SERVER_SOFTWARE'] . ' (' . $_SERVER['SERVER_NAME'] . ')');
//Non-apache fix for REQUEST_URI
	if (!isset($_SERVER['REQUEST_URI'])) {
		$_SERVER['REQUEST_URI'] = substr($_SERVER['PHP_SELF'], 1);
		if (isset($_SERVER['QUERY_STRING'])) {
			$_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
		}
	}
	$registry->set('uri', $_SERVER['REQUEST_URI']);
//main instance of data encryption
	$registry->set('dcrypt', new ADataEncryption());
}catch(AException $e){}
