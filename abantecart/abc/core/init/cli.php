<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2018 Belavier Commerce LLC
 *
 * This source file is subject to Open Software License (OSL 3.0)
 * License details is bundled with this package in the file LICENSE.txt.
 * It is also available at this URL:
 * <http://www.opensource.org/licenses/OSL-3.0>
 *
 * UPGRADE NOTE:
 * Do not edit or add to this file if you wish to upgrade AbanteCart to newer
 * versions in the future. If you wish to customize AbanteCart for your
 * needs please refer to http://www.abantecart.com for more information.
 */

namespace abc\commands;

use abc\core\ABC;
use abc\core\engine\{ ALoader, ExtensionsApi, Registry };
use abc\core\lib\Abac;
use abc\core\lib\ADB;
use abc\core\lib\OSUser;
use Exception;
use H;
use Illuminate\Events\Dispatcher;

// do check for vendor autoload file first
if (!is_file(dirname(__DIR__, 2).DS.'vendor'.DS.'autoload.php')) {
    echo "Initialisation...\n";
    $composer_phar = dirname(__DIR__).DS.'system'.DS.'temp'.DS.'composer.phar';
    if (!is_file($composer_phar)) {
        $temp_dir = dirname(dirname(__DIR__).DS.'system'.DS.'temp'.DS.'composer.phar');
        if (!is_dir($temp_dir)) {
            @mkdir($temp_dir, 0775, true);
        }
        if (!is_dir($temp_dir) || !is_writable($temp_dir)) {
            echo "Temporary directory ".$temp_dir." does not exists or not writable!\n\n";
            exit;
        }

        echo "Composer phar-package not found.\n"
            ."Trying to download Latest Composer into abc/system/temp directory. Please wait..\n";
        if (!copy(
            'https://getcomposer.org/composer.phar',
            dirname(__DIR__).DS.'system'.DS.'temp'.DS.'composer.phar')
        ) {
            exit("Error: Tried to download latest composer.phar file"
                ." from https://getcomposer.org/composer.phar but failed.\n".
                " Please download it manually into "
                .dirname(__DIR__).DS."system".DS."temp directory\n"
                ." OR run composer manually (see composer.json file)");
        }
    }

    exit("\n\e[0;31mError: /abc/vendor/autoload.php file not found. Please run command \e[0m\n\n
    php ".$composer_phar." install -d ".dirname(__DIR__, 2)."\n\n\e[0;31m to initialize a project!\e[0m\n\n");
}

if (!ini_get('date.timezone')) {
    date_default_timezone_set('UTC');
}

ABC::env('IS_ADMIN', true);
ABC::env('INDEX_FILE', 'index.php');
$charset = ABC::env('APP_CHARSET');
$charset = !$charset ? 'UTF-8' : $charset;
mb_internal_encoding($charset);
ini_set('default_charset', strtolower($charset));

//Set up common paths
$dir_root = !ABC::env('DIR_ROOT') ? dirname(__DIR__, 3).DS : ABC::env('DIR_ROOT');
$dir_app = !ABC::env('DIR_APP') ? dirname(__DIR__, 2).DS : ABC::env('DIR_APP');
$dir_public = !ABC::env('DIR_PUBLIC') ? $dir_root.'public'.DS : ABC::env('DIR_PUBLIC');
$dir_vendor = !ABC::env('DIR_VENDOR') ? $dir_app.'vendor'.DS : ABC::env('DIR_VENDOR');

$defaults = [
    'DIR_ROOT'           => $dir_root,
    'DIR_TESTS'          => $dir_app.'tests'.DS,
    'DIR_APP'            => $dir_app,
    'DIR_TEMPLATES'      => $dir_app.'templates'.DS,
    'DIR_APP_EXTENSIONS' => $dir_app.'extensions'.DS,
    'DIR_SYSTEM'         => $dir_app.'system'.DS,
    'CACHE'              => [
        'stores' =>
            [
                'file' => [
                    'path' => $dir_app.'system'.DS.'cache'.DS,
                ],
            ],
    ],
    'DIR_BACKUP'         => $dir_app.'system'.DS.'backup'.DS,
    'DIR_CORE'           => $dir_app.'core'.DS,
    'DIR_LIB'            => $dir_app.'core'.DS.'lib'.DS,
    'DIR_MODULES'        => $dir_app.'modules'.DS,
    'DIR_WORKERS'        => $dir_app.'modules'.DS.'workers'.DS,
    'DIR_IMAGES'         => $dir_public.'images'.DS,
    'DIR_DOWNLOADS'      => $dir_app.'downloads'.DS,
    'DIR_MIGRATIONS'     => $dir_app.'migrations'.DS,
    'DIR_CONFIG'         => $dir_app.'config'.DS,
    'DIR_LOGS'            => $dir_app.'system'.DS.'logs'.DS,
    'DIR_PUBLIC'          => $dir_public,
    'DIR_RESOURCES'       => $dir_public.DS.'resources'.DS,
    'DIR_VENDOR'          => $dir_vendor,
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
    'DIR_ASSETS_EXT'      => $dir_public.'extensions'.DS,
];
foreach ($defaults as $name => $value) {
    if (!ABC::env($name)) {
        ABC::env($name, $value);
    }
}
//load vendors classes

require_once ABC::env('DIR_VENDOR').'autoload.php';

// App Version
include_once('version.php');
ABC::env('VERSION', ABC::env('MASTER_VERSION').'.'.ABC::env('MINOR_VERSION').'.'.ABC::env('VERSION_BUILT'));
$dir_lib = ABC::env('DIR_LIB');
require_once($dir_lib.'exceptions.php');
require_once($dir_lib.'error.php');
require_once($dir_lib.'warning.php');

//load base libraries
require_once('base.php');

$registry = Registry::getInstance();
require_once('admin.php');

//put OSUser class inside registry
$registry->set('os_user', new OSUser());

// Loader
registerClass($registry, 'load', 'ALoader', [$registry], '\abc\core\engine\ALoader', [$registry]);
$registry->set('load', new ALoader($registry));

// URL Class
registerClass($registry, 'html', 'AHtml', [$registry], '\abc\core\engine\AHtml', [$registry]);

// Database
if (ABC::env('DB_CURRENT_DRIVER')) {
    $db_config = ABC::env('DATABASES');
    $registry->set('db', new ADB($db_config[ABC::env('DB_CURRENT_DRIVER')]));
    H::setDBUserVars();
}


//session
$session_id = 'CLI';
registerClass($registry, 'session', 'ASession', [$session_id], '\abc\core\lib\ASession', [$session_id]);


// Config
if (ABC::env('DB_CURRENT_DRIVER')) {
    // Cache
    registerClass($registry, 'cache', 'cache', [ABC::env('CACHE')['driver']], '\abc\core\lib\AbcCache', ['file']);
    registerClass($registry, 'config', 'AConfig', [$registry], '\abc\core\lib\AConfig', [$registry]);
    registerClass(
        $registry,
        'language',
        'ALanguageManager',
        [$registry],
        '\abc\core\lib\ALanguageManager',
        [$registry]);

    //override urls
    $url = $registry->get('config')->get('config_url');
    $parse_url = parse_url($url);

    ABC::env('HTTP_DIR_NAME', rtrim(dirname($parse_url['path']), '/.\\'), true);
    // Admin HTTP
    ABC::env('AUTO_SERVER', '//'.$parse_url['host'].$parse_url['path'], true);
    ABC::env('HTTP_SERVER', 'http:'.ABC::env('AUTO_SERVER'), true);
    ABC::env('HTTP_CATALOG', ABC::env('HTTP_SERVER'), true);
    ABC::env('HTTP_EXT', ABC::env('HTTP_SERVER').'extensions/', true);
    ABC::env('HTTP_IMAGE', ABC::env('HTTP_SERVER').'images/', true);
    ABC::env('HTTP_DIR_RESOURCES', ABC::env('HTTP_SERVER').'resources/', true);
    //we use Protocol-relative URLs here
    ABC::env('HTTPS_IMAGE', ABC::env('AUTO_SERVER').'images/', true);
    ABC::env('HTTPS_DIR_RESOURCES', ABC::env('AUTO_SERVER').'resources/', true);
    //Admin HTTPS
    if ($parse_url['scheme'] == 'https') {
        ABC::env('HTTPS_SERVER', 'https:'.ABC::env('AUTO_SERVER'), true);
        ABC::env('HTTPS_CATALOG', ABC::env('HTTPS_SERVER'), true);
        ABC::env('HTTPS_EXT', ABC::env('HTTPS_SERVER').'extensions/', true);
    } else {
        ABC::env('HTTPS_SERVER', ABC::env('HTTP_SERVER'), true);
        ABC::env('HTTPS_CATALOG', ABC::env('HTTP_CATALOG'), true);
        ABC::env('HTTPS_EXT', ABC::env('HTTP_EXT'), true);
    }
}

registerClass($registry, 'im', 'AIMManager', [], "\abc\core\lib\AIMManager", []);
if($registry->has('db')){
   registerClass($registry, 'order_status', 'AOrderStatus', [$registry], "\abc\core\lib\AOrderStatus", [$registry]);
}
registerClass($registry, 'download', 'ADownload', [], "\abc\core\lib\ADownload", []);

// Log
$registry->set('log', ABC::getObjectByAlias('ALog', [['app' => 'cli.log']]));

// Document
registerClass($registry, 'document', 'ADocument', [], '\abc\core\lib\ADocument', []);

//main instance of data encryption
registerClass($registry, 'dcrypt', 'ADataEncryption', [], '\abc\core\lib\ADataEncryption', []);

// Extensions api
$extensions = new ExtensionsApi();
$extensions->loadAvailableExtensions();
$registry->set('extensions', $extensions);

//register event listeners
/**
 * @var Dispatcher $evd
 */
$evd = ABC::getObjectByAlias('EventDispatcher');
if(is_object($evd)) {
    foreach (ABC::env('EVENTS') as $event_alias => $listeners) {
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
}

// functions

/**
 * @param string|array $result
 */
function showResult($result)
{
    if (is_string($result) && $result) {
        echo $result."\n";
    } elseif (is_array($result) && $result) {
        showError("Runtime errors occurred\n");
        foreach ($result as $error) {
            showError($error);
        }
    }
}

/**
 * @param array $args
 *
 * @return array
 */
function parseOptions($args = [])
{
    $options = [];

    foreach ($args as $v) {
        preg_match('/^--(.*)$/', $v, $match);
        $arg = $match[1];
        $array = explode('=', $arg);
        if ($match && sizeof($array) > 1) {
            list($name, $value) = $array;
        } elseif ($match) {
            $name = $arg;
            $value = true;
        } else {
            $name = $v;
            $value = true;
        }
        $options[$name] = trim($value);
    }
    return $options;
}

/**
 * @param string $text
 */
function showError($text)
{
    echo("\033[0;31m".$text."\033[0m\n");
}

/**
 * @param Exception $e | Error $e
 */
function showException($e)
{
    showError('Error: '.$e->getMessage().PHP_EOL.$e->getTraceAsString());
}

/**
 * @param string $script_name - name of executor
 * @param array  $options
 */
function showHelpPage($script_name = '', $options = [])
{
    global $registry;
    $script_name = $script_name == 'help' ? '' : strtolower($script_name);
    //first of all get list of scripts
    $executors = glob(ABC::env('DIR_APP').'commands'.DS.'*.php');
    $help = [];

    foreach ($executors as $exec) {
        $name = strtolower(pathinfo($exec, PATHINFO_FILENAME));
        $executor = getExecutor($name, true);

        //skip if
        if ($script_name && $script_name != $name) {
            continue;
        }
        if (is_array($executor)) {
            echo $executor['message']."\n";
            $registry->get('log')->write($executor['message']);
            continue;
        }
        if (method_exists($executor, 'help')) {
            //get help_info from executor
            $help[$name] = $executor->help($options);
        }
        unset($executor);
    }

    echo "AbanteCart version \e[0;32m".ABC::env('VERSION')."\e[0m\n\n";


    if($help) {
        echo "\e[1;33mUsage:\e[0m\n";
        echo "\t[command]:[action] [--arg1=value] [--arg2=value]...\n";
        echo "\n\e[1;33mAvailable commands:\e[0m\n\n";
        foreach ($help as $command => $help_info) {
            $output = "\t\e[93m".$command."\n";
            if (!$help_info) {
                continue;
            }
            foreach ($help_info as $action => $desc) {
                $output .= "\t\t"."\e[0;32m".$command.":".$action." - ".$desc['description']."\e[0m"."\n";
                if ($desc['arguments']) {
                    $output .= "\tArguments:\n";
                    foreach ($desc['arguments'] as $argument => $arg_info) {
                        $arg_text = "\t\t\e[0;32m".$argument."\e[0m";
                        $arg_text .= $arg_info['default_value'] ? "[=value]" : "";
                        $arg_text = str_pad($arg_text, 40, ' ');
                        $output .= $arg_text;
                        if ($arg_info['required'] === 'conditional') {
                            $output .= " \t\t"."\e[1;33m[conditional]\e[0m";
                        } elseif ($arg_info['required']) {
                            $output .= " \t\t"."\033[0;31m[required]\e[0m";
                        } else {
                            $output .= " \t\t"."\e[37m[optional]\e[0m";
                        }
                        if ($arg_info['description']) {
                            $output .= "\t".$arg_info['description'];
                        }
                        $output .= "\n";
                    }
                }
                if ($desc['example']) {
                    $output .= "\n\tExample:   ";
                    $output .= $desc['example']."\n\n";
                }
            }
            echo $output."\n\n";
        }
    }else{
        showError('Command "'. $script_name.'" not found.');
    }

    echo "\n";
    exit(1);
}

/**
 * @param string $name
 * @param bool   $silent_mode - silent mode
 *
 * @return array | \abc\commands\Install
 */
function getExecutor($name, $silent_mode = false)
{

    $run_file = ABC::env('DIR_APP').'commands'.DS.$name.'.php';
    if (!is_file($run_file)) {
        $error_text = "Error: Script ".$run_file." not found!";
        if (!$silent_mode) {
            showError($error_text);
            exit(1);
        } else {
            return [
                'result'  => false,
                'message' => $error_text,
            ];
        }
    }
    try {
        require_once $run_file;
        /**
         * @var \abc\commands\Install $executor
         */
        $class_name = "\abc\commands\\".$name;
        if (class_exists($class_name)) {
            return $executor = new $class_name();
        } else {
            throw new \Exception('Class '.$class_name.' not found in '.$run_file);
        }
    } catch (\Exception $e) {
        $error_text = 'Error: '.$e->getMessage();
        if (!$silent_mode) {
            showError($error_text);
            exit(1);
        } else {
            return [
                'result'  => false,
                'message' => $error_text,
            ];
        }
    }
}

/**
 * @param Registry $registry
 * @param string   $item_name
 * @param string   $alias
 * @param array    $arguments
 * @param string   $default_class
 * @param array    $default_arguments
 *
 * @throws \abc\core\lib\AException
 */
function registerClass($registry, $item_name, $alias, $arguments, $default_class, $default_arguments)
{
    $class_name = ABC::getFullClassName($alias);
    $instance = H::getInstance($class_name, $arguments, $default_class, $default_arguments);
    $registry->set($item_name, $instance);
}

return $registry;
