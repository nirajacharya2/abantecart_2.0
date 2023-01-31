<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2022 Belavier Commerce LLC
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

namespace abc\core;

use abc\core\engine\Registry;
use abc\core\engine\ARouter;
use abc\core\lib\ADebug;
use abc\core\lib\AException;
use abc\models\BaseModel;
use H;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

if (php_sapi_name() != 'cli') {
    ob_start();
}

require __DIR__ . DS . 'ABCBase.php';

/**
 * Class ABC
 *
 * @package abc
 */
class ABC extends ABCBase
{
    protected static $env = [];
    protected static $class_map = [];
    protected static $model_map = [];
    static $stage_name = '';

    /**
     * ABC constructor.
     *
     * @param string $file - full filename of file that returns active environment stage name
     */
    public function __construct($file = '')
    {
        //load and put config into environment
        $stage_name = '';
        if (!$file || !is_file($file)) {
            $stage_name = @include(
                dirname(getcwd())
                . DS . 'abc'
                . DS . 'config'
                . DS . 'enabled.config.php'
            );
        } else {
            $stage_name = @include($file);
        }

        @include __DIR__ . DS . 'init' . DS . 'version.php';
        //load config and classmap from abc/config and extensions/*/config directories
        static::loadConfig($stage_name);
        //register autoloader
        spl_autoload_register([$this, 'loadClass']);
    }

    /**
     * Autoloader for classes from abc namespace
     *
     * @param string $className - full class name
     *
     * @return bool
     */
    function loadClass($className)
    {

        $rootName = explode('\\', $className)[0];
        if ($rootName != 'abc') {
            return false;
        }

        $path = str_replace('\\', DS, $className);

        $fileName = ABC::env('DIR_ROOT') . $path . '.php';

        if (is_file($fileName)) {
            require_once $fileName;
            return true;
        } else {
            //try to find class with low case name
            $lcPath = strtolower($path);
            $fName = ABC::env('DIR_ROOT') . $lcPath . '.php';
            if (is_file($fName)) {
                require_once $fName;
                return true;
            }
        }

        //try to find by Abantecart prefix
        if (str_starts_with(basename($fileName), 'A')) {
            $dirname = dirname($fileName) . DS;
            $filename = substr(basename($fileName), 1);
            $filename = implode("_", preg_split(
                '/(^[^A-Z]+|[A-Z][^A-Z]+)/',
                $filename,
                -1,
                PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE
            ));
            if (is_file($dirname . $filename)) {
                require_once $dirname . $filename;
                return true;
            } elseif (is_file($dirname . mb_strtolower($filename))) {
                require_once $dirname . mb_strtolower($filename);
                return true;
            }
        }
        // looking for class by namespace path
        if (str_contains($className, 'Controller')) {
            $shortClassName = basename(str_replace("\\", DS, $className));
            $pre = str_replace($shortClassName, '', $className);
            $pre = str_replace('\\', '/', $pre);
            $shortClassName = str_replace('Controller', '', $shortClassName);
            $arr = array_map('strtolower', preg_split('/(?=[A-Z])/', $shortClassName));
            $filename = ABC::env('DIR_ROOT') . $pre . implode(DS, $arr) . '.php';
            if (is_file($filename)) {
                require_once $filename;
                return true;
            }
        }
        return false;
    }

    /**
     * @return string
     */
    static function getStageName()
    {
        return static::$stage_name;
    }

    /**
     * Load default stage environment
     */
    public function loadDefaultStage()
    {
        //load default config into environment
        static::loadConfig();
    }

    /**
     * @param string $stage_name
     *
     * @return bool
     */
    public static function loadConfig($stage_name = 'default')
    {
        $config_sections = ['config', 'events', 'model', 'reports', 'main_entities', 'order'];
        foreach ($config_sections as $config_section) {
            $KEY = strtoupper($config_section);
            $file_name = $config_section . '.php';
            $file = dirname(getcwd())
                . DS . 'abc' . DS
                . 'config' . DS
                . $stage_name . DS
                . $file_name;
            $config = @include($file);

            if ($config) {
                //if we load additional configs - place it as key of env array
                if ($config_section != 'config') {
                    static::$env[$KEY] = $config;
                } else {
                    static::$env = array_merge(static::$env, $config);
                }
                static::$stage_name = $stage_name;
            } else {
                //interrupt when stage config not found
                if ($config_section == 'config') {
                    return false;
                }
            }

            $ext_dirs = glob(
                dirname(getcwd())
                . DS . 'abc' . DS
                . 'extensions' . DS
                . '*' . DS
                . 'config' . DS
                . $stage_name . DS
            );

            foreach ($ext_dirs as $cfg_dir) {
                $cfg_file = $cfg_dir . DS . $config_section . '.php';

                if (is_file($cfg_file)) {
                    $ext_config = @include($cfg_file);
                    if (is_array($ext_config)) {
                        //if we load additions configs - place it as key of env array
                        if ($config_section == 'config') {
                            foreach ($ext_config as $n => $v) {
                                if (is_array($v)) {
                                    $prev = static::$env[$n] ?? [];
                                    static::$env[$n] = array_merge($prev, $v);
                                } else {
                                    static::$env[$n] = $v;
                                }
                            }
                        } else {
                            $prev = static::$env[$KEY] ?? [];
                            static::$env[$KEY] = array_merge_recursive(
                                $prev,
                                $ext_config
                            );
                        }
                    }
                }
            }
        }

        static::loadClassMap($stage_name);
        static::loadModelClassMap($stage_name);

        return true;
    }

    /**
     * @param string $stage_name
     *
     * @return bool
     */
    public static function loadClassMap($stage_name = 'default')
    {
        $classmap_file = dirname(getcwd())
            . DS . 'abc' . DS
            . 'config' . DS
            . $stage_name . DS
            . 'classmap.php';
        if (is_file($classmap_file)) {
            static::$class_map = @include($classmap_file);
        }

        if (!static::$class_map) {
            return false;
        }

        $ext_dirs = glob(
            dirname(getcwd())
            . DS . 'abc' . DS
            . 'extensions' . DS
            . '*' . DS
            . 'config' . DS
            . $stage_name . DS
        );
        foreach ($ext_dirs as $cfg_dir) {
            $classmap_file = $cfg_dir . DS . 'classmap.php';
            if (is_file($classmap_file)) {
                $ext_classmap = @include($classmap_file);
                if (is_array($ext_classmap)) {
                    static::$class_map = array_merge(static::$class_map, $ext_classmap);
                }
            }
        }

        return true;
    }

    /**
     * @param string $stage_name
     *
     * @return bool
     */
    public static function loadModelClassMap($stage_name = 'default')
    {
        static::$model_map = self::env('MODEL')['MORPH_MAP'];
        if (!static::$model_map) {
            return false;
        }
        return true;
    }

    /**
     * Function for adding class alias into classmap from outside
     * @param $alias
     * @param $fullClassName
     *
     * @return bool
     */
    public static function addClassToMap($alias, $fullClassName)
    {
        if (!$alias || !$fullClassName) {
            return false;
        }
        static::$class_map[$alias] = $fullClassName;
        return true;
    }

    /**
     * Static method for saving environment values into static property
     *
     * @param string | array $name
     * @param mixed|null $value
     * @param bool $override - force set
     *
     * @return null|string|array
     */
    public static function env($name, $value = null, $override = false)
    {
        //getting
        if ($value === null && !is_array($name)) {
            $name = trim($name);
            //check environment values
            if (!sizeof(static::$env)) {
                // DO NOT ALLOW RUN APP WITH EMPTY ENVIRONMENT
                exit('Fatal Error: empty environment! Please check abc/config directory for data consistency.');
            }
            if (isset(static::$env[$name])) {
                return static::$env[$name];
            } //getting all keys
            elseif ($name == '*') {
                return static::$env;
            } //getting by key mask
            elseif (str_ends_with($name, '*')) {
                $output = [];
                $name = trim($name, '*');
                foreach (static::$env as $n => $v) {
                    if (str_starts_with($n, $name)) {
                        $output[$n] = $v;
                    }
                }
                return $output;
            }
        } // setting
        else {
            if (is_array($name)) {

                //TODO: add check for existing value.
                // If string - do not make it an array by second write!
                // To replicate just set env value via array twice
                foreach ($name as $k => $v) {
                    if (!array_key_exists($k, static::$env) || $override) {
                        static::$env[$k] = $v;
                    }
                }
                return true;
            } else {
                //when set one value
                if (!array_key_exists($name, static::$env) || $override) {
                    static::$env[$name] = $value;
                    return true;
                } else {
                    if (class_exists(ADebug::class)) {
                        ADebug::warning(
                            'Environment option override',
                            9101,
                            'Try to put var ' . $name . ' into abc-environment, but it already exists!');
                    }
                    return false;
                }
            }
        }
        if (class_exists(ADebug::class)) {
            $dbg = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 5);
            ADebug::warning(
                'Environment option "' . $name . '" not found',
                9101,
                'ABC Environment Issue: key ' . $name . ' not found. (' . $dbg[0]['file'] . ':' . $dbg[0]['line'] . ')');
        }
        return null;
    }

    static function getEnv()
    {
        return static::$env;
    }

    static function getClassMap()
    {
        return static::$class_map;
    }

    static function getModelClassMap()
    {
        return static::$model_map;
    }

    /**
     * Method returns full name of class if it exists
     *
     * @param string $class_alias
     *
     * @return bool|object|BaseModel
     */
    static function getFullClassName(string $class_alias)
    {
        if (isset(static::$class_map[$class_alias])) {
            if (is_array(static::$class_map[$class_alias])) {
                return static::$class_map[$class_alias][0];
            } else {
                return static::$class_map[$class_alias];
            }
        }
        if (isset(static::$model_map[$class_alias])) {
            if (is_array(static::$model_map[$class_alias])) {
                return static::$model_map[$class_alias][0];
            } else {
                return static::$model_map[$class_alias];
            }
        }
        //looking for models
        if (class_exists("\abc\models\\" . $class_alias)) {
            return "\abc\models\\" . $class_alias;
        }
        foreach (glob(dirname(getcwd()) . DS . 'abc' . DS . 'models' . DS . '*', GLOB_ONLYDIR) as $dir) {
            if (in_array(basename($dir), ['admin', 'storefront'])) {
                continue;
            }
            $path = '\abc\models\\' . basename($dir) . '\\' . $class_alias;
            if (class_exists($path)) {
                return $path;
            }
        }

        $extDirs = glob(dirname(getcwd()) . DS . 'abc' . DS . 'extensions' . DS . '*' );
        foreach ($extDirs as $dir) {
            $path = '\abc\extensions\\' . basename($dir) . '\models\\' . $class_alias;
            if (class_exists($path)) {
                return $path;
            }
        }
        //if still not found model - looking into subdirectories
        foreach ($extDirs as $extDir) {
            foreach (glob($extDir . DS . '*', GLOB_ONLYDIR) as $dir) {
                if (in_array(basename($dir), ['admin', 'storefront'])) {
                    continue;
                }
                $path = '\abc\extensions\\' . basename(dirname($dir, 2)) . '\models\\' . basename($dir) . '\\' . $class_alias;
                if (class_exists($path)) {
                    return $path;
                }
            }
        }
        return false;
    }

    /**
     * Method returns full name of class if it exists
     *
     * @param string $class_alias
     *
     * @param array $args
     *
     * @return bool|object
     */
    static function getObjectByAlias(string $class_alias, $args = [])
    {
        if (isset(static::$class_map[$class_alias])) {
            try {
                if (is_array(static::$class_map[$class_alias])) {
                    $class_name = static::$class_map[$class_alias][0];
                } else {
                    $class_name = static::$class_map[$class_alias];
                }

                $args = $args ?: static::getClassDefaultArgs($class_alias);

                $refMethod = new ReflectionMethod($class_name, '__construct');
                $params = $refMethod->getParameters();

                //trick for usage references as constructor parameter
                $re_args = [];
                foreach ($params as $key => $param) {
                    if ($param->isPassedByReference()) {
                        $re_args[$key] = &$args[$key];
                    } else {
                        $re_args[$key] = $args[$key];
                    }
                }

                $reflector = new ReflectionClass($class_name);
                return $reflector->newInstanceArgs($re_args);
            } catch (ReflectionException $e) {
            }
        }

        return false;
    }

    /**
     * Method returns full name of class if it exists
     *
     * @param string $class_alias
     *
     * @param array $args
     *
     * @return bool|object
     * @throws AException
     */
    static function getModelObjectByAlias(string $class_alias, $args = [])
    {
        if (!isset(static::$model_map[$class_alias])) {
            throw new AException('Class with alias '.$class_alias.' not found in config/*/model.php file', 1000);
        }
            if (is_array(static::$model_map[$class_alias])) {
                $class_name = static::$model_map[$class_alias][0];
            } else {
                $class_name = static::$model_map[$class_alias];
            }

            $args = $args ?: static::getClassDefaultArgs($class_alias);
            return new  $class_name(...$args);

    }

    /**
     * Get arguments for class constructor
     *
     * @param string $class_alias
     *
     * @return array|mixed
     */
    static function getClassDefaultArgs(string $class_alias)
    {
        if (!isset(static::$class_map[$class_alias]) || !is_array(static::$class_map[$class_alias])) {
            return [];
        }
        $args = static::$class_map[$class_alias];
        if (is_array($args)) {
            array_shift($args);
        } else {
            $args = [];
        }
        return $args;
    }

    public function run()
    {

        $this->validateApp();

        // New Installation
        if (!static::env('DATABASES')) {
            if (is_file(static::env('DIR_ROOT') . 'install' . DS . 'index.php')) {
                header('Location: ../install/index.php');
            } else {
                header('Location: static_pages/?file='
                    . basename(__FILE__) . '&message=Fatal+error:+Cannot+load+environment!');
            }
            exit;
        }

        $registry = Registry::getInstance();
        $this->init();
        ob_clean();

        ADebug::checkpoint('init end');

        //Route to request process
        $router = new ARouter($registry);
        $registry->set('router', $router);
        $router->processRoute(static::env('ROUTE'));

        // Output
        $registry->get('response')->output();

        if (static::env('IS_ADMIN') === true
            && $registry->get('config')->get('config_maintenance')
            && $registry->get('user')->isLogged()
        ) {
            $user_id = $registry->get('user')->getId();
            H::startStorefrontSession($user_id);
        }

        //Show cache stats if debugging
//        if ($registry->get('config')->get('config_debug')) {
        /* ??????????????
        //TODO. how to collect statistic for laravel cache???
        ADebug::variable('Cache statistics: ',
             $registry->get('cache')->stats()."\n");*/
//        }

        ADebug::checkpoint('app end');

        //display debug info
        if ($router->getRequestType() == 'page') {
            ADebug::display();
        }
    }

    public function init()
    {
        /** @see ./init/app.php */
        require dirname(getcwd()) . DS
            . 'abc' . DS
            . 'core' . DS
            . 'init' . DS
            . 'app.php';
    }

    protected function validateApp()
    {
        // Required PHP Version
        if (version_compare(phpversion(), static::env('MIN_PHP_VERSION'), '<')) {
            exit(static::env('MIN_PHP_VERSION')
                . '+ Required for AbanteCart to work properly! '
                . 'Please contact your system administrator or host service provider.');
        }
        if (!function_exists('simplexml_load_file')) {
            exit("simpleXML functions are not available.'
            .' Please contact your system administrator or host service provider.");
        }
    }
}
