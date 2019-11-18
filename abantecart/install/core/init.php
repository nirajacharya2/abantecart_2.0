<?php

namespace abc;

// set default encoding for multibyte php mod
use abc\core\ABC;
use abc\core\engine\AHtml;
use abc\core\engine\ALoader;
use abc\core\engine\ExtensionsApi;
use abc\core\engine\Registry;
use abc\core\lib\AbcCache;
use abc\core\lib\AConfig;
use abc\core\lib\ADataEncryption;
use abc\core\lib\ADB;
use abc\core\lib\ADocument;
use abc\core\lib\ALog;
use abc\core\lib\AMessage;
use abc\core\lib\ARequest;
use abc\core\lib\AResponse;
use abc\core\lib\ASession;
use abc\core\lib\CSRFToken;

mb_internal_encoding(ABC::env('APP_CHARSET'));
ini_set('default_charset', 'utf-8');

$dir_sep = DS;
$dir_root = dirname(__DIR__, 2).$dir_sep;
$dir_app = $dir_root.'abc'.$dir_sep;
$dir_public = $dir_root.'public'.$dir_sep;
$dir_install = dirname(__DIR__).$dir_sep;

//Set up common paths
ABC::env(
    [
        'MIN_PHP_VERSION'    => '7.0.0',
        'DIR_ROOT'           => $dir_root,
        'DIR_APP'            => $dir_app,
        'DIR_PUBLIC'         => $dir_public,
        'DIR_INSTALL'        => $dir_install,
        'DIR_VENDOR'         => $dir_app.'vendor'.$dir_sep,
        'DIR_APP_EXTENSIONS' => $dir_app.'extensions'.$dir_sep,
        'DIR_SYSTEM'         => $dir_app.'system'.$dir_sep,
        'DIR_CORE'           => $dir_app.'core'.$dir_sep,
        'DIR_LIB'            => $dir_app.'core'.$dir_sep.'lib'.$dir_sep,
        'DIR_DOWNLOADS'      => $dir_app.'downloads'.$dir_sep,
        'DIR_CONFIG'         => $dir_app.'config'.$dir_sep,
        'CACHE'              => [
            'driver' => 'file',
            'stores' => [
                'file' => [
                    'path' => $dir_app.'system'.$dir_sep.'cache'.$dir_sep,
                    'ttl'  => 15,
                ],
            ],
        ],
        'DIR_LOGS'           => $dir_app.'system'.$dir_sep.'logs'.$dir_sep,
        'DIR_TEMPLATES'      => $dir_app.'templates'.$dir_sep,
        'DIR_ASSETS_EXT'     => $dir_public.'extensions'.$dir_sep,
        'DIR_IMAGES'         => $dir_public.'images'.$dir_sep,
        'DIR_RESOURCES'      => $dir_public.'resources'.$dir_sep,
        'DIR_LANGUAGES'      => $dir_app.'languages'.$dir_sep,
        'DIR_BACKUP'         => $dir_app.'system'.$dir_sep.'backup'.$dir_sep,
        'DIR_DATA'           => $dir_app.'system'.$dir_sep.'data'.$dir_sep,

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

        'POSTFIX_OVERRIDE' => '.override',
        'POSTFIX_PRE'      => '.pre',
        'POSTFIX_POST'     => '.post',
    ]
);

// AbanteCart Version
include($dir_app.'core'.$dir_sep.'init'.$dir_sep.'version.php');

// Detect if localhost is used.
if (!isset($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = 'localhost';
}

// Detect https
if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == '1')) {
    ABC::env('HTTPS', true);
} elseif (isset($_SERVER['HTTP_X_FORWARDED_SERVER'])
    && ($_SERVER['HTTP_X_FORWARDED_SERVER'] == 'secure'
        || $_SERVER['HTTP_X_FORWARDED_SERVER'] == 'ssl')) {
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

//load vendors classes
if (is_file(ABC::env('DIR_VENDOR').'autoload.php')) {
    require ABC::env('DIR_VENDOR').'autoload.php';
}

// Error Reporting
error_reporting(E_ALL);
$dir_lib = $dir_app.'core'.$dir_sep.'lib'.$dir_sep;
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

//generate unique session name.
//NOTE: This is a session name not to confuse with actual session id. Candidate to renaming
ABC::env('SESSION_ID', 'AC_INSTALL');

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
        $_SERVER['DOCUMENT_ROOT'] =
            str_replace('\\', '/', substr($_SERVER['SCRIPT_FILENAME'], 0, 0 - strlen($_SERVER['PHP_SELF'])));
    }
}

if (!isset($_SERVER['DOCUMENT_ROOT'])) {
    if (isset($_SERVER['PATH_TRANSLATED'])) {
        $_SERVER['DOCUMENT_ROOT'] = str_replace('\\', '/',
            substr(str_replace('\\\\', '\\', $_SERVER['PATH_TRANSLATED']), 0, 0 - strlen($_SERVER['PHP_SELF'])));
    }
}

if (!isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = substr($_SERVER['PHP_SELF'], 1);
    if (isset($_SERVER['QUERY_STRING'])) {
        $_SERVER['REQUEST_URI'] .= '?'.$_SERVER['QUERY_STRING'];
    }
}

//load base libraries
require_once $dir_app.'core'.$dir_sep.'init'.$dir_sep.'base.php';

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

// Database
if (ABC::env('DATABASES')) {
    $db_config = ABC::env('DATABASES');
    //check database and tables in it
    $adb = new ADB($db_config[ABC::env('DB_CURRENT_DRIVER')]);
    $result = $adb->query("SHOW TABLES;");
    if ($result->num_rows) {
        $registry->set('db', $adb);
    }
}

// Cache
$registry->set('cache', new AbcCache('file'));

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
require_once $dir_app.'core'.$dir_sep.'init'.$dir_sep.'admin.php';

//Messages
$registry->set('messages', new AMessage());

// Log
$registry->set('log', new ALog(['app' => 'error.txt']));

// Document
$registry->set('document', new ADocument());

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
$registry->set('dcrypt', new ADataEncryption());

// Extensions api
$extensions = new ExtensionsApi();

//for admin we load all available(installed) extensions.
//This is a solution to make controllers and hooks available for extensions that are in the status off.
$extensions->loadAvailableExtensions();

$registry->set('extensions', $extensions);
unset($extensions);

$template = 'default';
$config->set('original_admin_template', $template);
$config->set('admin_template', $template);
