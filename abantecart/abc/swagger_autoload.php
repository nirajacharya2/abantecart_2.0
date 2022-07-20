<?php
define('DS', DIRECTORY_SEPARATOR);
require_once(__DIR__.'/core/ABC.php');
use abc\core\ABC;

ABC::env('MIN_PHP_VERSION', '7.0.0');
if (version_compare(phpversion(), ABC::env('MIN_PHP_VERSION'), '<') == true) {
    die(ABC::env('MIN_PHP_VERSION')
        .'+ Required for AbanteCart to work properly! Please contact your system administrator or host service provider.');
}

if (substr(php_sapi_name(), 0, 3) != 'cli' && !empty($_SERVER['REMOTE_ADDR'])) {
    //not command line!!
    echo "Not implemented! <br> \n";
    exit;
}

// Load Configuration
// Real path (operating system web root) to the directory where abantecart is installed
$root_path = __DIR__;

// Windows IIS Compatibility
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    ABC::env('IS_WINDOWS', true);
    $root_path = str_replace('\\', '/', $root_path);
}

ABC::env('DIR_ROOT', $root_path);
ABC::env('DIR_CORE', ABC::env('DIR_ROOT').'/core/');

require_once(ABC::env('DIR_ROOT').'/config/enabled.config.php');
// sign of admin side for controllers run from dispatcher
$_GET['s'] = ABC::env('ADMIN_SECRET');
// Load all initial set up
require_once(ABC::env('DIR_ROOT').'/models/InitializeModel.php');
require_once(__DIR__.'/core/engine/contracts/AttributeInterface.php');
require_once(__DIR__.'/core/engine/registry.php');
//require_once(__DIR__.'/core/init/base.php');
require_once(__DIR__.'/core/engine/controller.php');
require_once(__DIR__.'/core/engine/controller_api.php');
require_once(__DIR__.'/core/engine/secure_controller_api.php');

require_once(__DIR__.'/core/lib/ApiErrorResponse.php');
require_once(__DIR__.'/core/lib/ApiSuccessResponse.php');


function includeDir($path) {
    $dir      = new RecursiveDirectoryIterator($path);
    $iterator = new RecursiveIteratorIterator($dir);
    foreach ($iterator as $file) {
        $fname = $file->getFilename();
        if (preg_match('%\.php$%', $fname)) {
            require_once $file->getPathname();
        }
    }
}

includeDir(__DIR__.'/controllers/admin/api');
includeDir(__DIR__.'/controllers/storefront/api');
includeDir(__DIR__.'/docs/api');


spl_autoload_register(function ($class) {
});
