<?php

namespace abc\core;

use abc\core\engine\Registry;
use abc\core\helper\AHelperUtils;
use abc\core\engine\ARouter;
use abc\core\lib\ADebug;
use abc\core\lib\AError;
use abc\core\lib\AException;

require 'abc_base.php';

/**
 * Class ABC
 *
 * @package abc
 */
class ABC extends ABCBase
{
    protected static $env = [];
    protected static $class_map = [];
    static $stage_name;

    /**
     * ABC constructor.
     *
     * @param string $file
     */
    public function __construct($file = '')
    {
        //load and put config into environment
        $stage_name = '';
        if(!$file || !is_file($file)) {
            $stage_name = @include(dirname(__DIR__).'/config/enabled.config.php');
            $file_name = $stage_name.'.config.php';
            $file = dirname(__DIR__).'/config/'.$file_name;
        }

        $config = @include($file);
        if($config) {
            self::env($config);
            self::$stage_name = $stage_name;
        }
    }

    static function getStageName(){
        return self::$stage_name;
    }

    public function loadDefaultStage(){
        //load and put config into environment
        $config = @include(dirname(__DIR__).'/config/default.config.php');
        if (isset($config['default'])) {
            self::env((array)$config['default']);
        }
        self::$stage_name = 'default';
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
        if ($value === null && ! is_array($name)) {
            //check environment values
            if(!sizeof(self::$env)){
                // DO NOT ALLOW RUN APP WITH EMPTY ENVIRONMENT
                exit('Fatal Error: empty environment!');
            }
            return isset(self::$env[$name]) ? self::$env[$name] : null;
        }
        // if need to set batch of values
        else {
            if (is_array($name)) {
                self::$env = array_merge(self::$env, $name);
                return true;
            } else {
                //when set one value
                if ( ! array_key_exists($name, self::$env) || $override) {
                    self::$env[$name] = $value;
                    return true;
                } else {
                    if( class_exists('\abc\core\lib\ADebug')) {
                        ADebug::warning(
                            'Environment variable override',
                            AC_ERR_USER_WARNING,
                            'Try to put var '.$name.' into abc-environment, but it already exists!');
                    }
                }
            }
        }
        return null;
    }

    /**
     * Method returns full name of class if it exists
     * @param $class_name
     *
     * @return bool|string
     */
    static function classes(string $class_name){
        if(isset( self::$class_map[$class_name])){
            return self::$class_map[$class_name];
        }else{
            return class_exists($class_name) ? $class_name : false;
        }
    }

    public function run()
    {
        $this->_validate_app();

        // New Installation
        if ( ! self::env('DATABASES')) {
            if(is_file(self::env('DIR_ROOT').'install/index.php')) {
                header('Location: ../install/index.php');
            }else{
                header('Location: static_pages/?file='.basename(__FILE__).'&message=Fatal+error:+Cannot+load+environment!');
            }
            exit;
        }

        require 'init/app.php';
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
            && $registry->get('user')->isLogged()) {
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

    protected function _validate_app()
    {

        // Required PHP Version

        if (version_compare(phpversion(), self::env('MIN_PHP_VERSION'), '<') == true) {
            exit(self::env('MIN_PHP_VERSION').'+ Required for AbanteCart to work properly! Please contact your system administrator or host service provider.');
        }

        if ( ! function_exists('simplexml_load_file')) {
            exit("simpleXML functions are not available. Please contact your system administrator or host service provider.");
        }

    }
}