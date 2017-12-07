<?php

namespace abc\cli;

use abc\ABC;
use abc\core\engine\Registry;
use abc\lib\ADataEncryption;
use abc\lib\ADocument;
use abc\lib\ALog;

// Error Reporting
error_reporting(E_ALL);
if ( ! ini_get('date.timezone')) {
    date_default_timezone_set('UTC');
}

require dirname(__DIR__).'/abc.php';
//run constructor of ABC class to load config
new ABC();
ABC::env('IS_ADMIN', true);
$charset = ABC::env('APP_CHARSET');
$charset = !$charset ? 'UTF-8' : $charset;
mb_internal_encoding($charset);
ini_set('default_charset', strtolower($charset));

//Set up common paths
$dir_root = ! ABC::env('DIR_ROOT') ? dirname(__DIR__,2).'/' : ABC::env('DIR_ROOT');
$dir_app = ! ABC::env('DIR_APP') ? dirname(__DIR__).'/' : ABC::env('DIR_APP');
$dir_public = ! ABC::env('DIR_PUBLIC') ? $dir_root.'public/' : ABC::env('DIR_PUBLIC');
$dir_vendor = ! ABC::env('DIR_VENDOR') ? $dir_root.'vendor/' : ABC::env('DIR_VENDOR');

$defaults = [
    'DIR_ROOT'            => $dir_root,
    'DIR_APP'             => $dir_app,
    'DIR_TEMPLATES'       => $dir_app.'templates/',
    'DIR_APP_EXTENSIONS'  => $dir_app.'extensions/',
    'DIR_SYSTEM'          => $dir_app.'system/',
    'DIR_CORE'            => $dir_app.'core/',
    'DIR_LIB'             => $dir_app.'lib/',
    'DIR_IMAGE'           => $dir_app.'images/',
    'DIR_DOWNLOAD'        => $dir_app.'download/',
    'DIR_CONFIG'          => $dir_app.'config/',
    'DIR_CACHE'           => $dir_app.'system/cache/',
    'DIR_LOGS'            => $dir_app.'system/logs/',
    'DIR_PUBLIC'          => $dir_public,
    'DIR_VENDOR'          => $dir_vendor,
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
    'DIR_ASSETS_EXT'      => $dir_public.'extensions/',
];
foreach ($defaults as $name => $value) {
    if ( ! ABC::env($name)) {
        ABC::env($name, $value);
    }
}


// App Version
include($dir_app.'core/init/version.php');
$dir_lib = ABC::env('DIR_LIB');
require_once($dir_lib.'debug.php');
require_once($dir_lib.'exceptions.php');
require_once($dir_lib.'error.php');
require_once($dir_lib.'warning.php');

//load base libraries
require_once(ABC::env('DIR_CORE').'init/base.php');

$registry = Registry::getInstance();
require_once(ABC::env('DIR_CORE').'init/admin.php');

// Log
$registry->set('log', new ALog(ABC::env('DIR_LOGS').'cli_log.txt'));

// Document
$registry->set('document', new ADocument());

//main instance of data encryption
$registry->set('dcrypt', new ADataEncryption());

