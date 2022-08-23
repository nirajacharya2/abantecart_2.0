<?php

use abc\core\ABC;
use function abc\commands\showException;

define('DS', DIRECTORY_SEPARATOR);
$rootDir = dirname(getcwd()).DS.'abc';

function listAllFiles($dir) {
    $array = array_diff(scandir($dir), array('.', '..'));

    foreach ($array as &$item) {
        $item = $dir . $item;
    }
    unset($item);
    foreach ($array as $item) {
        if (is_dir($item)) {
            $array = array_merge($array, listAllFiles($item . DIRECTORY_SEPARATOR));
        }
    }
    return $array;
}

function includeExtensionsApi($path) {
    foreach (listAllFiles($path) as $filename) {
        if (str_contains($filename, '.php') && str_contains($filename, 'api')) {
            require_once($filename);
        }
    }
}

try {
    require dirname(getcwd()).DS.'abc/core'.DS.'ABC.php';
    //run constructor of ABC class to load environment

    $ABC = new ABC();
    if (!$ABC::getStageName()) {
        $ABC->loadDefaultStage();
        echo "Default stage environment loaded.\n\n";
    }
    require dirname(getcwd()).DS.'abc/core'.DS.'init'.DS.'cli.php';

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

    includeDir($rootDir.'/controllers/admin/api');
    includeDir($rootDir.'/controllers/storefront/api');
    includeDir($rootDir.'/docs/api');
    includeExtensionsApi($rootDir.'/extensions/');

} catch (\Exception $e) {
    showException($e);
    exit(1);
}
