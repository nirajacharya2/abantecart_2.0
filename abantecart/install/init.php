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
use abc\core\Registry;
use abc\lib\ADataEncryption;
use abc\lib\ADocument;
use abc\lib\AException;
use abc\lib\ALog;
use abc\lib\ASession;
use abc\lib\CSRFToken;

mb_internal_encoding('UTF-8');
ini_set('default_charset', 'utf-8');

// Detect if localhost is used.
if (!isset($_SERVER['HTTP_HOST'])) {
	$_SERVER['HTTP_HOST'] = 'localhost';
}

// Detect https
if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == '1')) {
	define('HTTPS', true);
} elseif (isset($_SERVER['HTTP_X_FORWARDED_SERVER']) && ($_SERVER['HTTP_X_FORWARDED_SERVER'] == 'secure' || $_SERVER['HTTP_X_FORWARDED_SERVER'] == 'ssl')) {
	define('HTTPS', true);
} elseif (isset($_SERVER['SCRIPT_URI']) && (substr($_SERVER['SCRIPT_URI'], 0, 5) == 'https')) {
	define('HTTPS', true);
} elseif (isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], ':443') !== false)) {
	define('HTTPS', true);
}

// Detect http host
if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
	define('REAL_HOST', $_SERVER['HTTP_X_FORWARDED_HOST']);
} else {
	define('REAL_HOST', $_SERVER['HTTP_HOST']);
}

//Set up common paths
define('DIR_APP_EXTENSIONS', DIR_APP . 'extensions/');
define('DIR_SYSTEM', DIR_APP . 'system/');
define('DIR_CORE', DIR_APP . 'core/');
define('DIR_LIB', DIR_APP . 'lib/');
define('DIR_IMAGE', DIR_ASSETS . 'images/');
define('DIR_DOWNLOAD', DIR_APP . 'download/');
define('DIR_CONFIG', DIR_APP . 'config/');
define('DIR_CACHE', DIR_APP . 'var/cache/');
define('DIR_LOGS', DIR_APP . 'var/logs/');
define('DIR_VENDOR', dirname(dirname(__FILE__)) . '/vendor/');

// AbanteCart Version
include(DIR_APP.'core/init/version.php');

// Error Reporting
error_reporting(E_ALL);
require_once(DIR_LIB . 'debug.php');
require_once(DIR_LIB . 'exceptions.php');
require_once(DIR_LIB . 'error.php');
require_once(DIR_LIB . 'warning.php');

//define rt - route for application controller
if($_GET['rt']) {
	define('ROUTE', $_GET['rt']);
} else if($_POST['rt']){
	define('ROUTE', $_POST['rt']);
} else {
	define('ROUTE', 'index/home');
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
	define('DIRNAME_APP', 'abc/');
	define('DIRNAME_ASSETS', 'assets/');
	define('DIRNAME_EXTENSIONS', 'extensions/');
	define('DIRNAME_CORE', 'core/');
	define('DIRNAME_STORE', 'storefront/');
	define('DIRNAME_ADMIN', 'admin/');
	define('DIRNAME_IMAGES', 'images/');
	define('DIRNAME_CONTROLLERS', 'controllers/');
	define('DIRNAME_LANGUAGES', 'languages/');
	define('DIRNAME_TEMPLATE', 'template/');
	define('DIRNAME_TEMPLATES', 'templates/');

	define('DIR_APP_EXT', DIR_APP . DIRNAME_EXTENSIONS);
	define('DIR_ASSETS_EXT', DIR_ASSETS . DIRNAME_EXTENSIONS);
	/**
	 * @const DIR_APP
	 */
	require_once(DIR_APP.DIRNAME_CORE.'init/base.php');
	$registry = Registry::getInstance();
	require_once(DIR_APP.DIRNAME_CORE.'init/admin.php');

	// Session
	$registry->set('session', new ASession(SESSION_ID));

// CSRF Token Class
	$registry->set('csrftoken', new CSRFToken());

// Log
	$registry->set('log', new ALog(DIR_LOGS));

// Document
	$registry->set('document', new ADocument());

// AbanteCart Snapshot details
	$registry->set('snapshot',
			'AbanteCart/' . VERSION . ' ' . $_SERVER['SERVER_SOFTWARE'] . ' (' . $_SERVER['SERVER_NAME'] . ')');
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
