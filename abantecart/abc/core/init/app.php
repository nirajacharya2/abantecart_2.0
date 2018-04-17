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

// set default encoding for multibyte php mod
use abc\core\ABC;
use abc\core\engine\AHook;
use abc\core\engine\AHtml;
use abc\core\engine\ALanguage;
use abc\core\lib\ADebug;
use abc\core\lib\ALanguageManager;
use abc\core\engine\ALayout;
use abc\core\engine\ALoader;
use abc\core\engine\ExtensionsApi;
use abc\core\engine\Registry;
use abc\core\cache\ACache;
use abc\core\lib\ACart;
use abc\core\lib\AConfig;
use abc\core\lib\ACurrency;
use abc\core\lib\ACustomer;
use abc\core\lib\ADataEncryption;
use abc\core\lib\ADB;
use abc\core\lib\ADocument;
use abc\core\lib\ADownload;
use abc\core\lib\AError;
use abc\core\lib\AException;
use abc\core\lib\AIM;
use abc\core\lib\AIMManager;
use abc\core\lib\ALength;
use abc\core\lib\ALog;
use abc\core\lib\AMessage;
use abc\core\lib\AOrderStatus;
use abc\core\lib\ARequest;
use abc\core\lib\AResponse;
use abc\core\lib\ASession;
use abc\core\lib\ATax;
use abc\core\lib\AUser;
use abc\core\lib\AWeight;
use abc\core\lib\CSRFToken;

mb_internal_encoding(ABC::env('APP_CHARSET'));
ini_set('default_charset', 'utf-8');

$dir_sep = DIRECTORY_SEPARATOR;

// AbanteCart Version
include('version.php');
ABC::env('VERSION', ABC::env('MASTER_VERSION') . '.' . ABC::env('MINOR_VERSION').'.'. ABC::env('VERSION_BUILT'));
// Detect if localhost is used.
if ( ! isset($_SERVER['HTTP_HOST'])) {
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
} else {
    ABC::env('HTTPS', false);
}

// Detect http host
if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
    ABC::env('REAL_HOST', $_SERVER['HTTP_X_FORWARDED_HOST']);
} else {
    ABC::env('REAL_HOST', $_SERVER['HTTP_HOST']);
}

//Set up common paths

$dir_app = ABC::env('DIR_APP');
$dir_public = ABC::env('DIR_PUBLIC');

ABC::env(
    array(
        'DIR_VENDOR'          => $dir_app.'vendor'.$dir_sep,
        'DIR_APP_EXTENSIONS'  => $dir_app.'extensions'.$dir_sep,
        'DIR_SYSTEM'          => $dir_app.'system'.$dir_sep,
        'DIR_CORE'            => $dir_app.'core'.$dir_sep,
        'DIR_LIB'             => $dir_app.'core'.$dir_sep.'lib'.$dir_sep,
        'DIR_DOWNLOADS'       => $dir_app.'downloads'.$dir_sep,
        'DIR_CONFIG'          => $dir_app.'config'.$dir_sep,
        'DIR_CACHE'           => $dir_app.'system'.$dir_sep.'cache'.$dir_sep,
        'DIR_LOGS'            => $dir_app.'system'.$dir_sep.'logs'.$dir_sep,
        'DIR_TEMPLATES'       => $dir_app.'templates'.$dir_sep,
        'DIR_IMAGES'          => $dir_public.'images'.$dir_sep,
        'DIR_RESOURCES'       => $dir_public.'resources'.$dir_sep,
        'DIR_MIGRATIONS'      => $dir_app.'migrations'.$dir_sep
    )
);

//load vendors classes
require ABC::env('DIR_VENDOR').'autoload.php';

// Error Reporting
error_reporting(E_ALL);
$dir_lib = $dir_app.'core'.$dir_sep.'lib'.$dir_sep;
require_once($dir_lib.'debug.php');
ADebug::register();
require_once($dir_lib.'error.php');
require_once($dir_lib.'log.php');
require_once($dir_lib.'exceptions.php');
require_once($dir_lib.'error.php');
require_once($dir_lib.'warning.php');

//define rt - route for application controller
if (isset($_GET['rt']) && $_GET['rt']) {
    ABC::env('ROUTE', $_GET['rt']);
} else {
    if (isset($_POST['rt']) && $_POST['rt']) {
        ABC::env('ROUTE', $_POST['rt']);
    } else {
        ABC::env('ROUTE', 'index/home');
    }
}

//detect API call
$path_nodes = explode('/', ABC::env('ROUTE'));
ABC::env('IS_API', ($path_nodes[0] == 'a' ? true : false));

//Detect the section of the cart to access and build the path definitions
// s=admin or s=storefront (default nothing)
if (ABC::env('ADMIN_SECRET') !== null && (isset($_GET['s']) || isset($_POST['s'])) && ($_GET['s'] == ABC::env('ADMIN_SECRET') || $_POST['s'] == ABC::env('ADMIN_SECRET'))) {
    ABC::env(
        array(
            'IS_ADMIN'     => true,
            'DIR_LANGUAGES' => $dir_app.'languages'.$dir_sep.'admin'.$dir_sep,
            'DIR_BACKUP'   => $dir_app.'system'.$dir_sep.'backup'.$dir_sep,
            'DIR_DATA'     => $dir_app.'system'.$dir_sep.'data'.$dir_sep,
        )
    );

    //generate unique session name.
    //NOTE: This is a session name not to confuse with actual session id. Candidate to renaming
    ABC::env('SESSION_ID', ABC::env('UNIQUE_ID') ? 'AC_CP_'.strtoupper(substr(ABC::env('UNIQUE_ID'), 0, 10)) : 'AC_CP_PHPSESSID');
} else {
    ABC::env('IS_ADMIN', false);
    ABC::env('DIR_LANGUAGES', $dir_app.$dir_sep.'languages'.$dir_sep.'storefront'.$dir_sep);
    ABC::env('SESSION_ID', ABC::env('UNIQUE_ID') ? 'AC_SF_'.strtoupper(substr(ABC::env('UNIQUE_ID'), 0, 10)) : 'AC_SF_PHPSESSID');
    ABC::env('EMBED_TOKEN_NAME', 'ABC_TOKEN');
}

//set ini parameters for session
ini_set('session.use_trans_sid', 'Off');
ini_set('session.use_cookies', 'On');
ini_set('session.cookie_httponly', 'On');

// Magic Quotes
if (ini_get('magic_quotes_gpc')) {
    function clean($data)
    {
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

if ( ! ini_get('date.timezone')) {
    date_default_timezone_set('UTC');
}

if ( ! isset($_SERVER['DOCUMENT_ROOT'])) {
    if (isset($_SERVER['SCRIPT_FILENAME'])) {
        $_SERVER['DOCUMENT_ROOT'] = str_replace('\\', '/', substr($_SERVER['SCRIPT_FILENAME'], 0, 0 - strlen($_SERVER['PHP_SELF'])));
    }
}

if ( ! isset($_SERVER['DOCUMENT_ROOT'])) {
    if (isset($_SERVER['PATH_TRANSLATED'])) {
        $_SERVER['DOCUMENT_ROOT'] = str_replace('\\', '/', substr(str_replace('\\\\', '\\', $_SERVER['PATH_TRANSLATED']), 0, 0 - strlen($_SERVER['PHP_SELF'])));
    }
}

if ( ! isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = substr($_SERVER['PHP_SELF'], 1);
    if (isset($_SERVER['QUERY_STRING'])) {
        $_SERVER['REQUEST_URI'] .= '?'.$_SERVER['QUERY_STRING'];
    }
}

//paths for extensions
ABC::env(
    array(
        'DIRNAME_APP'         => 'abc'.$dir_sep,
        'DIRNAME_ASSETS'      => 'assets'.$dir_sep,
        'DIRNAME_EXTENSIONS'  => 'extensions'.$dir_sep,
        'DIRNAME_CORE'        => 'core'.$dir_sep,
        'DIRNAME_STORE'       => 'storefront'.$dir_sep,
        'DIRNAME_ADMIN'       => 'admin'.$dir_sep,
        'DIRNAME_IMAGES'      => 'images'.$dir_sep,
        'DIRNAME_CONTROLLERS' => 'controllers'.$dir_sep,
        'DIRNAME_LANGUAGES'   => 'languages'.$dir_sep,
        'DIRNAME_TEMPLATES'   => 'templates'.$dir_sep,
        'DIRNAME_TEMPLATE'    => 'template'.$dir_sep,
        'DIRNAME_VENDOR'      => 'vendor'.$dir_sep,

        'DIR_APP_EXTENSIONS' => $dir_app.'extensions'.$dir_sep,
        'DIR_ASSETS_EXT'     => $dir_public.'extensions'.$dir_sep,
    )
);

//load base libraries
require_once 'base.php';

// Registry
$registry = Registry::getInstance();

// Loader
$registry->set('load', new ALoader($registry));

// Request
$request = new ARequest();
$registry->set('request', $request);

// Response
$response = new AResponse();
$response->addHeader('Content-Type: text/html; charset=utf-8');
$registry->set('response', $response);
unset($response);

// URL Class
$registry->set('html', new AHtml($registry));

//Hook class
$hook = new AHook($registry);

// Database
$db_config = ABC::env('DATABASES');
$registry->set('db', new ADB($db_config[ABC::env('DB_CURRENT_DRIVER')]));

// Cache
$registry->set('cache', new ACache());

// Config
$config = new AConfig($registry);
$registry->set('config', $config);

// Session
$registry->set('session', new ASession(ABC::env('SESSION_ID')));
if ($config->has('current_store_id')) {
    $registry->get('session')->data['current_store_id'] = $config->get('current_store_id');
}

// CSRF Token Class
$registry->set('csrftoken', new CSRFToken());

// Set up HTTP and HTTPS based automatic and based on config
//Admin manager classes

if (ABC::env('IS_ADMIN') === true) {
    require_once 'admin.php';
} else {
    require_once 'storefront.php';
}

//Messages
$registry->set('messages', new AMessage());

// Log
$registry->set('log', ABC::getObject('ALog'));

// Document
$registry->set('document', new ADocument());

// AbanteCart Snapshot details
$registry->set('snapshot', 'AbanteCart/'.ABC::env('VERSION').' '.$_SERVER['SERVER_SOFTWARE'].' ('.$_SERVER['SERVER_NAME'].')');
//Non-apache fix for REQUEST_URI
if ( ! isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = substr($_SERVER['PHP_SELF'], 1);
    if (isset($_SERVER['QUERY_STRING'])) {
        $_SERVER['REQUEST_URI'] .= '?'.$_SERVER['QUERY_STRING'];
    }
}
$registry->set('uri', $_SERVER['REQUEST_URI']);

//main instance of data encryption 
$registry->set('dcrypt', new ADataEncryption());

// Extensions api
$dir_extensions = new ExtensionsApi();
if (ABC::env('IS_ADMIN') === true) {
    //for admin we load all available(installed) extensions.
    //This is a solution to make controllers and hooks available for extensions that are in the status off.
    $dir_extensions->loadAvailableExtensions();
} else {
    $dir_extensions->loadEnabledExtensions();
}
$registry->set('extensions', $dir_extensions);

//validate template
$is_valid = false;
$enabled_extensions = $dir_extensions->getEnabledExtensions();
unset($dir_extensions);

//check if we specify template directly
$template = 'default';

// see template in extensions
if (ABC::env('IS_ADMIN') !== true && ! empty($request->get['sf'])) {
    $template = preg_replace('/[^A-Za-z0-9_]+/', '', $request->get['sf']);
    $dir = $template.ABC::env('DIRNAME_STORE').ABC::env('DIRNAME_TEMPLATES').$template;
    if (in_array($template, $enabled_extensions) && is_dir(ABC::env('DIR_APP_EXTENSIONS').$dir)) {
        $is_valid = true;
    } else {
        $is_valid = false;
    }
}

//not found? Ok. See it in the core
if ( ! $is_valid) {
    //check template defined in settings
    if (ABC::env('IS_ADMIN') === true) {
        $template = $config->get('admin_template');
        $dir = 'templates'.$dir_sep.$template.$dir_sep.ABC::env('DIRNAME_ADMIN');
    } else {
        $template = $config->get('config_storefront_template');
        $dir = 'templates'.$dir_sep.$template.$dir_sep.ABC::env('DIRNAME_STORE');
    }

    if (in_array($template, $enabled_extensions) && is_dir(ABC::env('DIR_APP_EXTENSIONS').$dir)) {
        $is_valid = true;
    } else {
        $is_valid = false;
    }

    //check if this is default template
    if ( ! $is_valid && is_dir($dir_app.$dir)) {
        $is_valid = true;
    }
}

if ( ! $is_valid) {
    $error = new AError ('Template '.$template.' is not found - roll back to default');
    $error->toLog()->toDebug();
    $template = 'default';
}

if (ABC::env('IS_ADMIN') === true) {
    $config->set('original_admin_template', $config->get('admin_template'));
    $config->set('admin_template', $template);
    // Load language
    $lang_obj = new ALanguageManager($registry);
} else {
    $config->set('original_config_storefront_template', $config->get('config_storefront_template'));
    $config->set('config_storefront_template', $template);
    // Load language
    $lang_obj = new ALanguage($registry);
}

// Create Global Layout Instance
$registry->set('layout', new ALayout($registry, $template));

// load download class
$registry->set('download', new ADownload());

//load main language section
$lang_obj->load();
$registry->set('language', $lang_obj);
unset($lang_obj);
$hook->hk_InitEnd();

//load order status class
$registry->set('order_status', new AOrderStatus($registry));

//IM
if (ABC::env('IS_ADMIN') === true) {
    $registry->set('im', new AIMManager());
} else {
    $registry->set('im', new AIM());
}

if ( ! ABC::env('IS_ADMIN')) { // storefront load
    // Customer
    $registry->set('customer', new ACustomer($registry));
    // Tax
    $registry->set('tax', new ATax($registry));
    // Weight
    $registry->set('weight', new AWeight($registry));
    // Length
    $registry->set('length', new ALength($registry));
    // Cart
    $registry->set('cart', new ACart($registry));
} else {
    // User
    $registry->set('user', new AUser($registry));
}// end admin load

// Currency
$registry->set('currency', new ACurrency($registry));
