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
use abc\core\engine\AHook;
use abc\core\engine\AHtml;
use abc\core\engine\ALanguage;
use abc\lib\ALanguageManager;
use abc\core\engine\ALayout;
use abc\core\engine\ALoader;
use abc\core\engine\ExtensionsApi;
use abc\core\engine\Registry;
use abc\core\cache\ACache;
use abc\lib\ACart;
use abc\lib\AConfig;
use abc\lib\ACurrency;
use abc\lib\ACustomer;
use abc\lib\ADataEncryption;
use abc\lib\ADB;
use abc\lib\ADocument;
use abc\lib\ADownload;
use abc\lib\AError;
use abc\lib\AException;
use abc\lib\AIM;
use abc\lib\AIMManager;
use abc\lib\ALength;
use abc\lib\ALog;
use abc\lib\AMessage;
use abc\lib\AOrderStatus;
use abc\lib\ARequest;
use abc\lib\AResponse;
use abc\lib\ASession;
use abc\lib\ATax;
use abc\lib\AUser;
use abc\lib\AWeight;
use abc\lib\CSRFToken;

mb_internal_encoding(ABC::env('APP_CHARSET'));
ini_set('default_charset', 'utf-8');

// AbanteCart Version
include('version.php');

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
        'DIR_VENDOR'         => ABC::env('DIR_ROOT').'vendor/',
        'DIR_APP_EXTENSIONS' => $dir_app.'extensions/',
        'DIR_SYSTEM'         => $dir_app.'system/',
        'DIR_CORE'           => $dir_app.'core/',
        'DIR_LIB'            => $dir_app.'lib/',
        'DIR_DOWNLOAD'       => $dir_app.'download/',
        'DIR_CONFIG'         => $dir_app.'config/',
        'DIR_CACHE'          => $dir_app.'system/cache/',
        'DIR_LOGS'           => $dir_app.'system/logs/',
        'DIR_TEMPLATES'      => $dir_app.'templates/',
        'DIR_IMAGE'          => $dir_public.'images/',
        'DIR_RESOURCE'       => $dir_public.'resources/',
    )
);

//load vendors classes
require ABC::env('DIR_VENDOR').'autoload.php';

// Error Reporting
error_reporting(E_ALL);
$dir_lib = $dir_app.'lib/';
require_once($dir_lib.'debug.php');
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
if (ABC::env('ADMIN_PATH') !== null && (isset($_GET['s']) || isset($_POST['s'])) && ($_GET['s'] == ABC::env('ADMIN_PATH') || $_POST['s'] == ABC::env('ADMIN_PATH'))) {
    ABC::env(
        array(
            'IS_ADMIN'     => true,
            'DIR_LANGUAGE' => $dir_app.'languages/admin/',
            'DIR_BACKUP'   => $dir_app.'system/backup/',
            'DIR_DATA'     => $dir_app.'system/data/',
        )
    );

    //generate unique session name.
    //NOTE: This is a session name not to confuse with actual session id. Candidate to renaming
    ABC::env('SESSION_ID', ABC::env('UNIQUE_ID') ? 'AC_CP_'.strtoupper(substr(ABC::env('UNIQUE_ID'), 0, 10)) : 'AC_CP_PHPSESSID');
} else {
    ABC::env('IS_ADMIN', false);
    ABC::env('DIR_LANGUAGE', $dir_app.'/languages/storefront/');
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
        'DIRNAME_APP'         => 'abc/',
        'DIRNAME_ASSETS'      => 'assets/',
        'DIRNAME_EXTENSIONS'  => 'extensions/',
        'DIRNAME_CORE'        => 'core/',
        'DIRNAME_STORE'       => 'storefront/',
        'DIRNAME_ADMIN'       => 'admin/',
        'DIRNAME_IMAGES'      => 'images/',
        'DIRNAME_CONTROLLERS' => 'controllers/',
        'DIRNAME_LANGUAGES'   => 'languages/',
        'DIRNAME_TEMPLATES'   => 'templates/',
        'DIRNAME_TEMPLATE'    => 'template/',
        'DIRNAME_VENDOR'      => 'vendor/',

        'DIR_APP_EXTENSIONS' => $dir_app.'extensions/',
        'DIR_ASSETS_EXT'     => $dir_public.'extensions/',
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
$registry->set('log', new ALog(ABC::env('DIR_LOGS').$config->get('config_error_filename')));

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
$extensions = new ExtensionsApi();
if (ABC::env('IS_ADMIN') === true) {
    //for admin we load all available(installed) extensions.
    //This is a solution to make controllers and hooks available for extensions that are in the status off.
    $extensions->loadAvailableExtensions();
} else {
    $extensions->loadEnabledExtensions();
}
$registry->set('extensions', $extensions);

//validate template
$is_valid = false;
$enabled_extensions = $extensions->getEnabledExtensions();
unset($extensions);

//check if we specify template directly
$template = 'default';
if (ABC::env('IS_ADMIN') !== true && ! empty($request->get['sf'])) {
    $template = preg_replace('/[^A-Za-z0-9_]+/', '', $request->get['sf']);
    $dir = $template.ABC::env('DIRNAME_STORE').ABC::env('DIRNAME_TEMPLATES').$template;
    if (in_array($template, $enabled_extensions) && is_dir(ABC::env('DIR_APP_EXTENSIONS').$dir)) {
        $is_valid = true;
    } else {
        $is_valid = false;
    }
}

if ( ! $is_valid) {
    //check template defined in settings
    if (ABC::env('IS_ADMIN') === true) {
        $template = $config->get('admin_template');
        $dir = 'templates/'.$template.'/'.ABC::env('DIRNAME_ADMIN');
    } else {
        $template = $config->get('config_storefront_template');
        $dir = 'templates/'.$template.'/'.ABC::env('DIRNAME_STORE');
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
