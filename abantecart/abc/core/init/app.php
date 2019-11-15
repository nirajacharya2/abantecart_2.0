<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2018 Belavier Commerce LLC

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
use abc\core\engine\ALanguage;
use abc\core\lib\Abac;
use abc\core\lib\AbcCache;
use abc\core\lib\ADebug;
use abc\core\lib\ALanguageManager;
use abc\core\engine\ExtensionsApi;
use abc\core\engine\Registry;
use abc\core\lib\AError;
use H;
use Illuminate\Cache\CacheManager;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;

mb_internal_encoding(ABC::env('APP_CHARSET'));
ini_set('default_charset', 'utf-8');

// AbanteCart Version
ABC::env('VERSION', ABC::env('MASTER_VERSION').'.'.ABC::env('MINOR_VERSION').'.'.ABC::env('VERSION_BUILT'));
// Detect if localhost is used.
if (!isset($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = 'localhost';
}


// Detect https
if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == '1')) {
    ABC::env('HTTPS', true);
} elseif (
    isset($_SERVER['HTTP_X_FORWARDED_SERVER'])
    && ($_SERVER['HTTP_X_FORWARDED_SERVER'] == 'secure'
        || $_SERVER['HTTP_X_FORWARDED_SERVER'] == 'ssl')
) {
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
    [
        'DIR_VENDOR'         => $dir_app.'vendor'.DS,
        'DIR_APP_EXTENSIONS' => $dir_app.'extensions'.DS,
        'DIR_SYSTEM'         => $dir_app.'system'.DS,
        'DIR_CORE'           => $dir_app.'core'.DS,
        'DIR_LIB'            => $dir_app.'core'.DS.'lib'.DS,
        'DIR_MODELS'         => $dir_app.'models'.DS,
        'DIR_MODULES'        => $dir_app.'modules'.DS,
        'DIR_WORKERS'        => $dir_app.'modules'.DS.'workers'.DS,
        'DIR_DOWNLOADS'      => $dir_app.'downloads'.DS,
        'DIR_CONFIG'         => $dir_app.'config'.DS,
        'DIR_LOGS'           => $dir_app.'system'.DS.'logs'.DS,
        'DIR_TEMPLATES'      => $dir_app.'templates'.DS,
        'DIR_IMAGES'         => $dir_public.'images'.DS,
        'DIR_RESOURCES'      => $dir_public.'resources'.DS,
        'DIR_MIGRATIONS'     => $dir_app.'migrations'.DS,
    ]
);



//load vendors classes
require ABC::env('DIR_VENDOR').'autoload.php';

// Error Reporting
error_reporting(E_ALL);
$dir_lib = $dir_app.'core'.DS.'lib'.DS;
require_once $dir_lib.'debug.php';
ADebug::register();
require_once $dir_lib.'error.php';
require_once $dir_lib.'exceptions.php';
require_once $dir_lib.'error.php';
require_once $dir_lib.'warning.php';

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
if (ABC::env('ADMIN_SECRET') !== null
    && (isset($_GET['s']) || isset($_POST['s']))
    && ($_GET['s'] == ABC::env('ADMIN_SECRET') || $_POST['s'] == ABC::env('ADMIN_SECRET'))
) {
    ABC::env(
        [
            'IS_ADMIN'      => true,
            'DIR_LANGUAGES' => $dir_app.'languages'.DS,
            'DIR_BACKUP'    => $dir_app.'system'.DS.'backup'.DS,
            'DIR_DATA'      => $dir_app.'system'.DS.'data'.DS,
        ]
    );

    //generate unique session name.
    //NOTE: This is a session name not to confuse with actual session id. Candidate to renaming
    ABC::env(
        'SESSION_ID',
        ABC::env('UNIQUE_ID') ? 'AC_CP_'.strtoupper(substr(ABC::env('UNIQUE_ID'), 0, 10)) : 'AC_CP_PHPSESSID'
    );
} else {
    ABC::env('IS_ADMIN', false);
    ABC::env('DIR_LANGUAGES', $dir_app.DS.'languages'.DS);
    ABC::env(
        'SESSION_ID',
        ABC::env('UNIQUE_ID') ? 'AC_SF_'.strtoupper(substr(ABC::env('UNIQUE_ID'), 0, 10)) : 'AC_SF_PHPSESSID'
    );
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

if (!ini_get('date.timezone')) {
    date_default_timezone_set('UTC');
}

if (!isset($_SERVER['DOCUMENT_ROOT'])) {
    if (isset($_SERVER['SCRIPT_FILENAME'])) {
        $_SERVER['DOCUMENT_ROOT'] = str_replace(
            '\\',
            '/',
            substr($_SERVER['SCRIPT_FILENAME'], 0, 0 - strlen($_SERVER['PHP_SELF']))
        );
    }
}

if (!isset($_SERVER['DOCUMENT_ROOT'])) {
    if (isset($_SERVER['PATH_TRANSLATED'])) {
        $_SERVER['DOCUMENT_ROOT'] = str_replace(
            '\\',
            '/',
            substr(str_replace('\\\\', '\\', $_SERVER['PATH_TRANSLATED']), 0, 0 - strlen($_SERVER['PHP_SELF']))
        );
    }
}

if (!isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = substr($_SERVER['PHP_SELF'], 1);
    if (isset($_SERVER['QUERY_STRING'])) {
        $_SERVER['REQUEST_URI'] .= '?'.$_SERVER['QUERY_STRING'];
    }
}

//paths for extensions
ABC::env(
    [
        'DIRNAME_APP'         => 'abc'.DS,
        'DIRNAME_ASSETS'      => 'assets'.DS,
        'DIRNAME_EXTENSIONS'  => 'extensions'.DS,
        'DIRNAME_CORE'        => 'core'.DS,
        'DIRNAME_STORE'       => 'storefront'.DS,
        'DIRNAME_ADMIN'       => 'admin'.DS,
        'DIRNAME_IMAGES'      => 'images'.DS,
        'DIRNAME_CONTROLLERS' => 'controllers'.DS,
        'DIRNAME_LANGUAGES'   => 'languages'.DS,
        'DIRNAME_TEMPLATES'   => 'templates'.DS,
        'DIRNAME_TEMPLATE'    => 'template'.DS,
        'DIRNAME_VENDOR'      => 'vendor'.DS,
        'DIR_ASSETS_EXT'     => $dir_public.'extensions'.DS,
    ]
);

//load base libraries
require_once __DIR__.DS.'base.php';

// Registry
$registry = Registry::getInstance();

// Loader
registerClass($registry, 'load', 'ALoader', [$registry], '\abc\core\engine\ALoader', [$registry]);

// Request
registerClass($registry, 'request', 'ARequest', [$registry], '\abc\core\lib\ARequest', [$registry]);
$request = $registry->get('request');
// Response
registerClass($registry, 'response', 'AResponse', [$registry], '\abc\core\lib\AResponse', [$registry]);
$registry->get('response')->addHeader('Content-Type: text/html; charset=utf-8');

// URL Class
registerClass($registry, 'html', 'AHtml', [$registry], '\abc\core\engine\AHtml', [$registry]);

//Hook class
$hook = new AHook($registry);

// Database
$db_config = ABC::env('DATABASES');
registerClass(
    $registry,
    'db',
    'ADB',
    [$db_config[ABC::env('DB_CURRENT_DRIVER')]],
    '\abc\core\lib\ADB',
    [$db_config[ABC::env('DB_CURRENT_DRIVER')]]
);

if (php_sapi_name() == 'cli') {
    H::setDBUserVars();
}

// Cache
registerClass(
    $registry,
    'cache',
    'AbcCache',
    [ABC::env('CACHE')['driver']],
    '\abc\core\lib\AbcCache',
    ['file']
);


// Config
registerClass($registry, 'config', 'AConfig', [$registry], '\abc\core\lib\AConfig', [$registry]);
$config = $registry->get('config');

// Session
$session_id = ABC::env('SESSION_ID');
registerClass($registry, 'session', 'ASession', [$session_id], '\abc\core\lib\ASession', [$session_id]);

if ($config->has('current_store_id')) {
    $registry->get('session')->data['current_store_id'] = $config->get('current_store_id');
}

// CSRF Token Class
registerClass($registry, 'csrftoken', 'CSRFToken', [], '\abc\core\lib\CSRFToken', []);

// Set up HTTP and HTTPS based automatic and based on config
//Admin manager classes

if (ABC::env('IS_ADMIN') === true) {
    require_once 'admin.php';
} else {
    require_once 'storefront.php';
}

//Messages
registerClass($registry, 'messages', 'AMessage', [], '\abc\core\lib\AMessage', []);

// Log
$registry->set('log', ABC::getObjectByAlias('ALog'));

// Document
registerClass($registry, 'document', 'ADocument', [], '\abc\core\lib\ADocument', []);

// AbanteCart Snapshot details
$registry->set(
    'snapshot',
    'AbanteCart/'.ABC::env('VERSION').' '.$_SERVER['SERVER_SOFTWARE'].' ('.$_SERVER['SERVER_NAME'].')'
);
//Non-apache fix for REQUEST_URI
if (!isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = substr($_SERVER['PHP_SELF'], 1);
    if (isset($_SERVER['QUERY_STRING'])) {
        $_SERVER['REQUEST_URI'] .= '?'.$_SERVER['QUERY_STRING'];
    }
}
$registry->set('uri', $_SERVER['REQUEST_URI']);

//main instance of data encryption
registerClass($registry, 'dcrypt', 'ADataEncryption', [], '\abc\core\lib\ADataEncryption', []);

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

if (ABC::env('IS_ADMIN') !== true && !empty($request->get['sf'])) {
    $template = preg_replace('/[^A-Za-z0-9_]+/', '', $request->get['sf']);
    $dir = $template.ABC::env('DIRNAME_STORE').ABC::env('DIRNAME_TEMPLATES').$template;
    if (in_array($template, $enabled_extensions) && is_dir(ABC::env('DIR_APP_EXTENSIONS').$dir)) {
        $is_valid = true;
    } else {
        $is_valid = false;
    }
}

//not found? Ok. See it in the core
if (!$is_valid) {
    //check template defined in settings
    if (ABC::env('IS_ADMIN') === true) {
        $template = ABC::env('adminTemplate') ? ABC::env('adminTemplate') : $config->get('admin_template');
        $dir = 'templates'.DS.$template.DS.ABC::env('DIRNAME_ADMIN');
    } else {
        $template = $config->get('config_storefront_template');
        $dir = 'templates'.DS.$template.DS.ABC::env('DIRNAME_STORE');
    }

    if (in_array($template, $enabled_extensions) && is_dir(ABC::env('DIR_APP_EXTENSIONS').$template.DS.$dir)) {
        $is_valid = true;
    } else {
        $is_valid = false;
    }

    //check if this is default template
    if (!$is_valid && is_dir($dir_app.$dir)) {
        $is_valid = true;
    }
}

if (!$is_valid) {
    $error = new AError ('Template '.$template.' is not found - roll back to default');
    $error->toLog()->toDebug();
    $template = 'default';
}

/**
 * @var ALanguage | ALanguageManager $lang_obj
 */
if (ABC::env('IS_ADMIN') === true) {
    $config->set('original_admin_template', $config->get('admin_template'));
    $config->set('admin_template', $template);
    // Load language
    $class_name = ABC::getFullClassName('ALanguageManager');
    $lang_obj = H::getInstance($class_name, [$registry], "\abc\core\lib\ALanguageManager", [$registry]);
} else {
    $config->set('original_config_storefront_template', $config->get('config_storefront_template'));
    $config->set('config_storefront_template', $template);
    // Load language
    $class_name = ABC::getFullClassName('ALanguage');
    $lang_obj = H::getInstance($class_name, [$registry], "\abc\core\\engine\ALanguage", [$registry]);
}

// Create Global Layout Instance
registerClass(
    $registry,
    'layout',
    'ALayout',
    [$registry, $template],
    "\abc\core\\engine\ALayout",
    [$registry, $template]);

// load download class
registerClass($registry, 'download', 'ADownload', [], "\abc\core\lib\ADownload", []);


//load main language section
$registry->set('language', $lang_obj);
$lang_obj->load();
unset($lang_obj);
$hook->hk_InitEnd();


//load order status class
registerClass($registry, 'order_status', 'AOrderStatus', [$registry], "\abc\core\lib\AOrderStatus", [$registry]);
//load order class
registerClass($registry, 'order', 'AOrder', [$registry], "\abc\core\lib\AOrder", [$registry]);


//IM

$im_alias = ABC::env('IS_ADMIN') === true ? 'AIMManager' : 'AIM';
registerClass($registry, 'im', $im_alias, [], "\abc\core\lib\\".$im_alias, []);

// Weight
registerClass($registry, 'weight', 'AWeight', [$registry], '\abc\core\lib\AWeight', [$registry]);
// Length
registerClass($registry, 'length', 'ALength', [$registry], '\abc\core\lib\ALength', [$registry]);

if (!ABC::env('IS_ADMIN')) { // storefront load
    // Customer
    registerClass($registry, 'customer', 'ACustomer', [$registry], '\abc\core\lib\ACustomer', [$registry]);
    H::setDBUserVars();
    // Tax
    registerClass($registry, 'tax', 'ATax', [$registry], '\abc\core\lib\ATax', [$registry]);
    // Cart
    registerClass($registry, 'cart', 'ACart', [$registry], '\abc\core\lib\ACart', [$registry]);
    $checkout_data = [
        'cart' => $registry->get('cart'),
        'customer' => $registry->get('customer'),
        'guest' => $registry->get('session')->data['guest'],
        'order_id' => $registry->get('session')->data['order_id'],
        'shipping_address_id' => $registry->get('session')->data['shipping_address_id'],
        'shipping_method' => $registry->get('session')->data['shipping_method'],
        'payment_address_id' => $registry->get('session')->data['payment_address_id'],
        'payment_method' => $registry->get('session')->data['payment_method']
    ];
    // checkout
    registerClass(
        $registry,
        'checkout',
        'Checkout',
        [$registry, $checkout_data],
        '\abc\core\lib\Checkout',
        [$registry, $checkout_data]
    );
} else {
    // User
    registerClass($registry, 'user', 'AUser', [$registry], '\abc\core\lib\AUser', [$registry]);
    H::setDBUserVars();
    // checkout
    registerClass($registry, 'checkout', 'CheckoutAdmin', [$registry,[]], '\abc\core\lib\Checkout', [$registry,[]]);
}// end admin load

// Currency
registerClass($registry, 'currency', 'ACurrency', [$registry], '\abc\core\lib\ACurrency', [$registry]);

//register controllers event listeners
/**
 * @var Dispatcher $evd
 */
$evd = ABC::getObjectByAlias('EventDispatcher');
if(is_object($evd)) {
    foreach ((array)ABC::env('EVENTS') as $event_alias => $listeners) {
        foreach ($listeners as $listener) {
            $evd->listen($event_alias, $listener);
        }
    }
    $registry->set('events', $evd);
}



//register ABAC
/**
 * @var Abac $abac
 */
$abac = ABC::getObjectByAlias('ABAC', [ $registry ]);
if(is_object($abac)) {
    $registry->set('abac', $abac);
}else{
    throw new \Exception('Class with alias "ABAC" not found in the classmap!');
}

/**
 * @param Registry $registry
 * @param string   $item_name
 * @param string   $alias
 * @param array    $arguments
 * @param string   $default_class
 * @param array    $default_arguments
 *
 * @throws core\lib\AException
 */
function registerClass($registry, $item_name, $alias, $arguments, $default_class, $default_arguments)
{
    $class_name = ABC::getFullClassName($alias);
    $instance = H::getInstance($class_name, $arguments, $default_class, $default_arguments);
    $registry->set($item_name, $instance);
}
