<?php
/*
  AbanteCart, Ideal Open Source Ecommerce Solution
  http://www.abantecart.com

  Copyright 2011-2018 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.abantecart.com for more information.
*/

namespace abc\core\backend;

use abc\core\ABC;
use abc\core\engine\{AHtml, ALoader, ExtensionsApi, Registry};
use abc\core\cache\ACache;
use abc\core\helper\AHelperUtils;
use abc\core\lib\{
    AConfig, ADataEncryption, ADB, ADocument, ALanguageManager, ALog, ASession
};

$dir_sep = DIRECTORY_SEPARATOR;
if ($command != 'help:help') {
    if (!is_file(dirname(__DIR__, 2).$dir_sep.'vendor'.$dir_sep.'autoload.php')) {
        echo "Initialisation...\n";
        $composer_phar = dirname(__DIR__).$dir_sep.'system'.$dir_sep.'temp'.$dir_sep.'composer.phar';
        if (!is_file($composer_phar)) {
            $temp_dir = dirname(dirname(__DIR__).$dir_sep.'system'.$dir_sep.'temp'.$dir_sep.'composer.phar');
            if (!is_dir($temp_dir)) {
                @mkdir($temp_dir, 0775, true);
            }
            if (!is_dir($temp_dir) || !is_writable($temp_dir)) {
                echo "Temporary directory ".$temp_dir." does not exists or not writable!\n\n";
                exit;
            }

            echo "Composer phar-package not found.\nTrying to download Latest Composer into abc/system/temp directory. Please wait..\n";
            if (!copy('https://getcomposer.org/composer.phar', dirname(__DIR__).$dir_sep.'system'.$dir_sep.'temp'.$dir_sep.'composer.phar')) {
                exit("Error: Tried to download latest composer.phar file from https://getcomposer.org/composer.phar but failed.\n".
                    " Please download it manually into "
                    .dirname(__DIR__).$dir_sep."system".$dir_sep."temp directory\n"
                    ." OR run composer manually (see composer.json file)");
            }
        }

        exit("\n\e[0;31mError: /abc/vendor/autoload.php file not found. Please run command \e[0m\n\n
        php ".$composer_phar." install -d ".dirname(__DIR__, 2)."\n\n\e[0;31m to initialize a project!\e[0m\n\n");
    }
}

if (!ini_get('date.timezone')) {
    date_default_timezone_set('UTC');
}

require dirname(__DIR__).$dir_sep.'abc.php';
//run constructor of ABC class to load environment

$ABC = new ABC();
if (!$ABC::getStageName()) {
    $ABC->loadDefaultStage();
    echo "Default stage environment loaded.\n\n";
}

ABC::env('IS_ADMIN', true);
ABC::env('INDEX_FILE', 'index.php');
$charset = ABC::env('APP_CHARSET');
$charset = !$charset ? 'UTF-8' : $charset;
mb_internal_encoding($charset);
ini_set('default_charset', strtolower($charset));

//Set up common paths
$dir_root = !ABC::env('DIR_ROOT') ? dirname(__DIR__, 3).$dir_sep : ABC::env('DIR_ROOT');
$dir_app = !ABC::env('DIR_APP') ? dirname(__DIR__, 2).$dir_sep : ABC::env('DIR_APP');
$dir_public = !ABC::env('DIR_PUBLIC') ? $dir_root.'public'.$dir_sep : ABC::env('DIR_PUBLIC');
$dir_vendor = !ABC::env('DIR_VENDOR') ? $dir_app.'vendor'.$dir_sep : ABC::env('DIR_VENDOR');

$defaults = [
    'DIR_ROOT'            => $dir_root,
    'DIR_APP'             => $dir_app,
    'DIR_TEMPLATES'       => $dir_app.'templates'.$dir_sep,
    'DIR_APP_EXTENSIONS'  => $dir_app.'extensions'.$dir_sep,
    'DIR_SYSTEM'          => $dir_app.'system'.$dir_sep,
    'DIR_BACKUP'          => $dir_app.'system'.$dir_sep.'backup'.$dir_sep,
    'DIR_CORE'            => $dir_app.'core'.$dir_sep,
    'DIR_LIB'             => $dir_app.'core'.$dir_sep.'lib'.$dir_sep,
    'DIR_MODULES'         => $dir_app.'modules'.$dir_sep,
    'DIR_IMAGES'          => $dir_public.'images'.$dir_sep,
    'DIR_DOWNLOADS'       => $dir_app.'downloads'.$dir_sep,
    'DIR_MIGRATIONS'      => $dir_app.'migrations'.$dir_sep,
    'DIR_CONFIG'          => $dir_app.'config'.$dir_sep,
    'DIR_CACHE'           => $dir_app.'system'.$dir_sep.'cache'.$dir_sep,
    'DIR_LOGS'            => $dir_app.'system'.$dir_sep.'logs'.$dir_sep,
    'DIR_PUBLIC'          => $dir_public,
    'DIR_RESOURCES'       => $dir_public.$dir_sep.'resources'.$dir_sep,
    'DIR_VENDOR'          => $dir_vendor,
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
    'DIR_ASSETS_EXT'      => $dir_public.'extensions'.$dir_sep,
];
foreach ($defaults as $name => $value) {
    if (!ABC::env($name)) {
        ABC::env($name, $value);
    }
}
//load vendors classes
require ABC::env('DIR_VENDOR').'autoload.php';
// App Version
include('version.php');
ABC::env('VERSION', ABC::env('MASTER_VERSION').'.'.ABC::env('MINOR_VERSION').'.'.ABC::env('VERSION_BUILT'));
$dir_lib = ABC::env('DIR_LIB');
require_once($dir_lib.'debug.php');
require_once($dir_lib.'exceptions.php');
require_once($dir_lib.'error.php');
require_once($dir_lib.'warning.php');

//load base libraries
require_once('base.php');

$registry = Registry::getInstance();
require_once('admin.php');

// Loader
$registry->set('load', new ALoader($registry));

// URL Class
$registry->set('html', new AHtml($registry));

// Database
if (ABC::env('DB_CURRENT_DRIVER')) {
    $db_config = ABC::env('DATABASES');
    $registry->set('db', new ADB($db_config[ABC::env('DB_CURRENT_DRIVER')]));
    AHelperUtils::setDBUserVars();
}

// Config
if (ABC::env('DB_CURRENT_DRIVER')) {
    // Cache
    $registry->set('cache', new ACache());
    $config = new AConfig($registry);
    $registry->set('config', $config);
    $registry->set('language', new ALanguageManager($registry));
}
// Log
$log_classname = ABC::getFullClassName('ALog');
$registry->set('log', new $log_classname('backend.log'));

//session
$registry->set('session', new ASession('backend'));

// Document
$registry->set('document', new ADocument());

//main instance of data encryption
$registry->set('dcrypt', new ADataEncryption());

// Extensions api
$extensions = new ExtensionsApi();
$extensions->loadAvailableExtensions();
$registry->set('extensions', $extensions);

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
            showError("\t\t".$error);
        }
        exit(1);
    }
}

/**
 * @param array $args
 *
 * @return array
 */
function parseOptions($args = [])
{
    $options = array();

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
    echo("\n\033[0;31m".$text."\033[0m\n\n");
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
    $executors = glob(ABC::env('DIR_CORE').'backend'.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'*.php');
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

    echo "\n";
    exit(1);
}

/**
 * @param string $name
 * @param bool   $silent_mode - silent mode
 *
 * @return array | \abc\core\backend\Install
 */
function getExecutor($name, $silent_mode = false)
{
    $run_file = ABC::env('DIR_CORE').'backend'.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.$name.'.php';
    if (!is_file($run_file)) {
        $error_text = "Error: Script ".$run_file."   not found!";
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
         * @var \abc\core\backend\Install $executor
         */
        $class_name = "\abc\core\backend\\".$name;
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

return $registry;