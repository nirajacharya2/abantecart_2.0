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

namespace abc\core;

use abc\core\engine\Registry;
use abc\core\helper\AHelperUtils;
use abc\core\engine\ARouter;
use abc\core\lib\ADebug;
use ReflectionClass;

ob_start();

require __DIR__.DS.'abc_base.php';

/**
 * Class ABC
 *
 * @package abc
 */
class ABC extends ABCBase
{
    protected static $env = [];
    protected static $class_map = [];
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
                dirname(__DIR__)
                .DS.'config'
                .DS.'enabled.config.php'
            );
        }

        //load config and classmap from abc/config and extensions/*/config directories
        self::loadConfig($stage_name);
        //register autoloader
        spl_autoload_register([$this, 'loadClass'], false);

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
        if (substr($className, 0, 3) != 'abc') {
            return false;
        }

        $path = str_replace('\\', DS, $className);
        $fileName = ABC::env('DIR_ROOT').$path.'.php';
        if (is_file($fileName)) {
            require_once $fileName;
            return true;
        }
        return false;
    }

    /**
     * @return string
     */
    static function getStageName()
    {
        return self::$stage_name;
    }

    /**
     * Load default stage environment
     */
    public function loadDefaultStage()
    {
        //load and put config into environment
        self::loadConfig('default');
    }

    /**
     * @param string $stage_name
     *
     * @return bool
     */
    public static function loadConfig($stage_name = 'default')
    {
        $config_sections = ['config', 'events'];
        foreach ($config_sections as $config_section) {
            $KEY = strtoupper($config_section);
            $file_name = $stage_name.'.'.$config_section.'.php';
            $file = dirname(__DIR__).DS.'config'.DS.$file_name;
            $config = @include($file);

            if ($config) {
                //if we load additions configs - place it as key of env array
                if ($config_section != 'config') {
                    $config = [$KEY => $config];
                }
                self::env($config);
                self::$stage_name = $stage_name;
            } else {
                //interrupt when stage config not found
                if ($config_section == 'config') {
                    return false;
                }
            }

            $ext_dirs = glob(
                dirname(__DIR__).DS
                .'extensions'.DS
                .'*'.DS
                .'config'.DS
            );

            foreach ($ext_dirs as $cfg_dir) {
                $file = $cfg_dir.DS.'enabled.config.php';
                $config = @include($file);
                //if stage name of extension environment is empty - skip configs
                if (!$config) {
                    continue;
                }
                $cfg_file = $cfg_dir.DS.$config.'.'.$config_section.'.php';

                if (is_file($cfg_file)) {
                    $ext_config = @include_once($cfg_file);
                    if (is_array($ext_config)) {
                        //if we load additions configs - place it as key of env array
                        if ($config_section == 'config') {
                            self::$env += $ext_config;
                        } else {
                            self::$env[$KEY] = array_merge_recursive(
                                (array)self::$env[$KEY],
                                $ext_config
                            );
                        }
                    }
                }
            }
        }
        self::loadClassMap($stage_name);

        return true;
    }

    /**
     * @param string $stage_name
     *
     * @return bool
     */
    public static function loadClassMap($stage_name = 'default')
    {
        $classmap_file = dirname(__DIR__).DS.'config'.DS.$stage_name.'.classmap.php';
        if (is_file($classmap_file)) {
            self::$class_map = @include_once($classmap_file);
        }

        if (!self::$class_map) {
            return false;
        }

        $ext_dirs = glob(
            dirname(__DIR__).DS
            .'extensions'.DS
            .'*'.DS
            .'config'.DS
        );
        foreach ($ext_dirs as $cfg_dir) {
            $file = $cfg_dir.DS.'enabled.config.php';
            $config = @include($file);
            //if stage name of extension environment is empty - skip configs
            if(!$config){ continue; }
            if ($config) {
                $stage_name = $config;
            }
            $classmap_file = $cfg_dir.DS.$stage_name.'.classmap.php';
            if (is_file($classmap_file)) {
                $ext_classmap = @include_once($classmap_file);
                if (is_array($ext_classmap)) {
                    self::$class_map = array_merge(self::$class_map,$ext_classmap);
                }
            }
        }

        return true;
    }

    /**
     * Static method for saving environment values into static property
     *
     * @param string | array $name
     * @param mixed|null $value
     * @param bool $override - force set
     *
     * @return null
     */
    public static function env($name, $value = null, $override = false)
    {
        //if need to get
        if ($value === null && !is_array($name)) {
            //check environment values
            if (!sizeof(self::$env)) {
                // DO NOT ALLOW RUN APP WITH EMPTY ENVIRONMENT
                exit('Fatal Error: empty environment! Please check abc/config directory for data consistency.');
            }
            if( isset(self::$env[$name])){
                return self::$env[$name];
            }
        } // if need to set batch of values
        else {
            if (is_array($name)) {
                self::$env = array_merge(self::$env, $name);
                return true;
            } else {
                //when set one value
                if (!array_key_exists($name, self::$env) || $override) {
                    self::$env[$name] = $value;
                    return true;
                } else {
                    if (class_exists('\abc\core\lib\ADebug')) {
                        ADebug::warning(
                            'Environment option override',
                            9101,
                            'Try to put var '.$name.' into abc-environment, but it already exists!');
                    }
                    return false;
                }
            }
        }
        if (class_exists('\abc\core\lib\ADebug')) {
            $dbg = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 5);
            ADebug::warning(
                'Environment option "'.$name.'" not found',
                9101,
                'ABC Environment Issue: key '.$name.' not found. ('.$dbg[0]['file'].':'.$dbg[0]['line'].')');
        }
        return null;
    }

    static function getEnv()
    {
        return self::$env;
    }

    /**
     * Method returns full name of class if it exists
     *
     * @param $class_alias
     *
     * @return bool|string
     */
    static function getFullClassName(string $class_alias)
    {

        if (isset(self::$class_map[$class_alias])) {
            if (is_array(self::$class_map[$class_alias])) {
                return self::$class_map[$class_alias][0];

            } else {
                return self::$class_map[$class_alias];
            }
        } else {

            return class_exists($class_alias) ? $class_alias : false;
        }
    }

    /**
     * Method returns full name of class if it exists
     *
     * @param string $class_alias
     *
     * @param array $args
     *
     * @return bool|string
     */
    static function getObjectByAlias(string $class_alias, $args = [])
    {
        if (isset(self::$class_map[$class_alias])) {
            try {
                if (is_array(self::$class_map[$class_alias])) {
                    $class_name = self::$class_map[$class_alias][0];
                } else {
                    $class_name = self::$class_map[$class_alias];
                }

                $args = $args ? $args : self::getClassDefaultArgs($class_alias);

                $reflector = new ReflectionClass($class_name);
                return $reflector->newInstanceArgs($args);
            }catch(\ReflectionException $e){}
        }

        return false;
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
        if (!isset(self::$class_map[$class_alias]) || !is_array(self::$class_map[$class_alias])) {
            return [];
        }
        $args = self::$class_map[$class_alias];
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
        if (!self::env('DATABASES')) {
            if (is_file(self::env('DIR_ROOT').'install'.DS.'index.php')) {
                header('Location: ../install/index.php');
            } else {
                header('Location: static_pages/?file='
                    .basename(__FILE__).'&message=Fatal+error:+Cannot+load+environment!');
            }
            exit;
        }

        $this->init();
        ob_clean();

        $registry = Registry::getInstance();
        ADebug::checkpoint('init end');

        //Route to request process
        $router = new ARouter($registry);
        $registry->set('router', $router);
        $router->processRoute(self::env('ROUTE'));

        // Output
        $registry->get('response')->output();

        if (self::env('IS_ADMIN') === true
            && $registry->get('config')->get('config_maintenance')
            && $registry->get('user')->isLogged()
        ) {
            $user_id = $registry->get('user')->getId();
            AHelperUtils::startStorefrontSession($user_id);
        }

        //Show cache stats if debugging
        if ($registry->get('config')->get('config_debug')) {
            ADebug::variable('Cache statistics: ',
                $registry->get('cache')->stats()."\n");
        }

        ADebug::checkpoint('app end');

        //display debug info
        if ($router->getRequestType() == 'page') {
            ADebug::display();
        }
    }

    public function init()
    {
        require __DIR__.DS.'init'.DS.'app.php';
    }

    protected function validateApp()
    {
        // Required PHP Version
        if (version_compare(phpversion(), self::env('MIN_PHP_VERSION'), '<') == true) {
            exit(self::env('MIN_PHP_VERSION')
                .'+ Required for AbanteCart to work properly! '
                .'Please contact your system administrator or host service provider.');
        }
        if (!function_exists('simplexml_load_file')) {
            exit("simpleXML functions are not available.'
            .' Please contact your system administrator or host service provider.");
        }
    }
}