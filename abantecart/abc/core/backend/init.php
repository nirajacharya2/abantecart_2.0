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

use abc\ABC;
use abc\core\engine\ALoader;
use abc\core\engine\Registry;
use abc\lib\ACache;
use abc\lib\AConfig;
use abc\lib\ADataEncryption;
use abc\lib\ADB;
use abc\lib\ADocument;
use abc\lib\ALog;

// Error Reporting
error_reporting(E_ALL);


if(!is_file( dirname(__DIR__,3).'/vendor/autoload.php')){
	echo "Initialisation...\n";
	$composer_phar = dirname(__DIR__,2).'/system/temp/composer.phar';
	if(!is_file($composer_phar)) {
        echo "Download Latest Composer into abc/system/temp directory. Please wait..\n";
        if( ! copy('https://getcomposer.org/composer.phar', dirname(__DIR__, 2).'/system/temp/composer.phar') ) {
            exit( "Error: Tried to download latest composer.phar file from https://getcomposer.org/composer.phar but failed.\n".
                " Please download it manually into "
                .dirname(__DIR__, 2)."/system/temp/ directory\n"
                ." OR run composer manually (see composer.json file)" );
        }
    }

    exit("\n\e[0;31mError: Vendor folder not found. Please run command \e[0m\n\n
		cd ".dirname(__DIR__,3)." && php ".$composer_phar." install
	\n\n\e[0;31m to initialize a project!\e[0m\n\n");
}

if ( ! ini_get('date.timezone')) {
    date_default_timezone_set('UTC');
}

require dirname(__DIR__,2).'/abc.php';
//run constructor of ABC class to load environment
new ABC();
ABC::env('IS_ADMIN', true);
$charset = ABC::env('APP_CHARSET');
$charset = !$charset ? 'UTF-8' : $charset;
mb_internal_encoding($charset);
ini_set('default_charset', strtolower($charset));

//Set up common paths
$dir_root = ! ABC::env('DIR_ROOT') ? dirname(__DIR__,3).'/' : ABC::env('DIR_ROOT');
$dir_app = ! ABC::env('DIR_APP') ? dirname(__DIR__,2).'/' : ABC::env('DIR_APP');
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

// Loader
	$registry->set('load', new ALoader($registry));

// Database

	$registry->set('db', new ADB(
			array(
					'driver'    => ABC::env('DB_DRIVER'),
					'host'      => ABC::env('DB_HOSTNAME'),
					'username'  => ABC::env('DB_USERNAME'),
					'password'  => ABC::env('DB_PASSWORD'),
					'database'  => ABC::env('DB_DATABASE'),
					'prefix'    => ABC::env('DB_PREFIX'),
					'charset'   => ABC::env('DB_CHARSET'),
					'collation' => ABC::env('DB_COLLATION'),
			)
		)
	);

// Cache
	$registry->set('cache', new ACache());

// Config
	$config = new AConfig($registry);
	$registry->set('config', $config);

// Log
$registry->set('log', new ALog(ABC::env('DIR_LOGS').'cli_log.txt'));

// Document
$registry->set('document', new ADocument());

//main instance of data encryption
$registry->set('dcrypt', new ADataEncryption());


// functions

/**
 * @param string|array $result
 */
function showResult($result){
    if(is_string($result) && $result){
        echo $result."\n";
    }elseif(is_array($result) && $result){
        showError("Runtime errors occurred");
            foreach($result as $error){
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
function parseOptions($args = []){
	$options = array ();
	foreach ($args as $v) {
		$is_flag = preg_match('/^--(.*)$/', $v, $match);
		//skip commands
		if (!$is_flag){
			continue;
		}

		$arg = $match[1];
		$array = explode('=', $arg);
		if (sizeof($array) > 1) {
			list($name, $value) = $array;
		} else {
			$name = $arg;
			$value = true;
		}
		$options[$name] = trim($value);
	}
	return $options;
}

/**
 * @param string $text
 */
function showError($text){
	echo("\n\033[0;31m".$text."\033[0m\n\n");
}

/**
 *
 */
function showHelpPage(){
	global $registry;
	//first of all get list of scripts
	$executors = glob(__DIR__.'/scripts/*.php');
	$help = [];
	foreach($executors as $exec) {
		$name = basename($exec);
		$executor = getExecutor($name,true);
		if( is_array($executor) ){
			$registry->get('log')->write($executor['message']);
			continue;
		}
        if(method_exists($executor,'help')) {
		    //get help_info from executor
            $help[$name] = $executor->help();
        }
		unset($executor);
    }

	echo "AbanteCart version \e[0;32m".ABC::env('VERSION')."\e[0m\n\n";
	echo "\e[1;33mUsage:\e[0m\n";
	echo "\t[command]:[action] [--arg1=value] [--arg2=value]...\n";
	echo "\n\e[1;33mAvailable commands:\e[0m\n\n";
	foreach($help as $command => $help_info){
        $output = "\t\e[93m".$command."\n";
        if(!$help_info){ continue; }
        foreach($help_info as $action => $desc) {
            $output .= "\t\t"."\e[0;32m".$command.":".$action." - ".$desc['description']."\e[0m"."\n";
            if($desc['arguments']){
                $output .= "\tArguments:\n";
                foreach($desc['arguments'] as $argument => $arg_info) {
                    $output .= "\t\t\e[0;32m".$argument."\e[0m";
                    if($arg_info['default_value']){
                        $output .= "[=value]";
                    }
                    if ($arg_info['required']) {
                        $output .= " \t\t"."\033[0;31m[required]\e[0m";
                    } else {
                        $output .= "     \t\t"."\e[37m[optional]\e[0m";
                    }
                    if($arg_info['description']){
                        $output .= "\t".$arg_info['description'];
                    }
                    $output .= "\n";
                }
            }
            if($desc['example']){
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
 * @param bool $silent_mode - silent mode
 *
 * @return array | \abc\core\backend\Install
 */
function getExecutor($name, $silent_mode = false){
	$run_file = __DIR__.'/scripts/'.$name.'.php';
	if(!is_file($run_file)){
		$error_text = "Error: Script ".$run_file.".php not found!";
		if(!$silent_mode) {
			showError($error_text);
            exit(1);
        }else{
			return [
					'result' => false,
					'message'=> $error_text
					];
		}
	}
	try{
	    require $run_file;
		/**
		 * @var \abc\core\backend\Install $executor
		 */
		$class_name = "\abc\core\backend\\".$name;
		if(class_exists($class_name)) {
            return $executor = new $class_name();
        }else{
			throw new \Exception('Class '.$class_name.' not found in '.$run_file);
		}
	}catch(\Exception $e){
		$error_text = 'Error: '.$e->getMessage();
		if(!$silent_mode) {
			showError($error_text);
			exit(1);
		}else{
			return [
					'result' => false,
					'message'=> $error_text
					];
		}
	}
}

return $registry;