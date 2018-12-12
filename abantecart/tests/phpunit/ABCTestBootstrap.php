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

namespace abantecart\tests;

use abc\core\ABC;
use abc\core\engine\Registry;
use abc\core\lib\ACustomer;
use abc\core\lib\ARequest;
use abc\core\lib\AResponse;
use abc\core\lib\AUser;
use Exception;
use ReflectionClass;

set_include_path(__DIR__);
define('DS', DIRECTORY_SEPARATOR);

//run init before tests run
new AbanteCartTestBootstrap();


/**
 * Class AbanteCartTestBootstrap
 *
 */

class AbanteCartTestBootstrap
{
    protected $registry;

    public function __construct()
    {
        require_once dirname(__DIR__, 2).DS.'abc'.DS.'core'.DS.'abc.php';

        //run constructor of ABC class to load environment

        $ABC = new ABC();
        $stage_name = $_ENV['stage'] ?: $ABC::getStageName();

        if (!$stage_name) {
            $ABC->loadDefaultStage();
            echo "Default stage environment loaded.\n\n";
        }
        //load core config for stage
        $ABC::loadConfig($stage_name);

        ABC::env('DIR_TESTS', dirname(__DIR__,1).DS);
        ABC::env('DIR_VENDOR', dirname(__DIR__, 2).DS.'abc'.DS.'vendor'.DS);
        //load tests config for stage

        $this->loadConfig($stage_name);
        require_once ABC::env('DIR_APP').'core'.DS.'init'.DS.'base.php';
        require_once ABC::env('DIR_APP').'core'.DS.'init'.DS.'cli.php';

        $GLOBALS['error_descriptions'] = 'ABC v'.ABC::env('VERSION').' PhpUnit Test';

        $dirname = dirname(__FILE__);
        $dirname = dirname($dirname);

        $dirname = dirname($dirname).'/public_html';
        define('ABC_TEST_ROOT_PATH', $dirname);
        define('ABC_TEST_HTTP_HOST', 'travis-ci.org');
        define('ABC_TEST_PHP_SELF', 'abantecart/abantecart_2.0/abantecart/public/index.php');

        $_SERVER['HTTP_HOST'] = ABC_TEST_HTTP_HOST;
        $_SERVER['PHP_SELF'] = ABC_TEST_PHP_SELF;

    }


    /**
     * @param string $stage_name
     *
     * @return bool
     */
    public function loadConfig($stage_name = 'default')
    {
        $config_sections = ['config', 'events', 'model'];
        foreach ($config_sections as $config_section) {
            $KEY = strtoupper($config_section);
            $file_name = $config_section.'.php';
            $file = ABC::env('DIR_TESTS')
                    .'phpunit'.DS
                    .'abc'.DS
                    .'config'.DS
                    .$stage_name.DS
                    .$file_name;
            $config = @include($file);

            if ($config) {
                echo "Note: Loading additional environment set from tests/phpunit/abc/config/".$stage_name
                    ." and extensions test folders\n\n\n";

                //if we load additional configs - place it as key of env array
                if ($config_section == 'config') {
                    foreach($config as $k=>$v) {
                        ABC::env($k, $v, true);
                    }
                }else{
                    ABC::env($KEY, $config, true);
                }
            }

            $ext_dirs = glob(
                ABC::env('DIR_APP').DS
                .'extensions'.DS
                .'*'.DS
                .'tests'.DS
                .'abc'.DS
                .'config'.DS
                .$stage_name.DS
            );

            foreach ($ext_dirs as $cfg_dir) {
                $cfg_file = $cfg_dir.DS.$config_section.'.php';

                if (is_file($cfg_file)) {
                    $ext_config = @include_once($cfg_file);
                    if (is_array($ext_config)) {
                        //if we load additions configs - place it as key of env array
                        if ($config_section == 'config') {
                            $arr = $ext_config;
                        } else {
                            $arr = array_merge_recursive( (array)ABC::env($KEY), $ext_config);
                        }
                        foreach($arr as $k=>$v) {
                            ABC::env($k, $v, true);
                        }
                    }
                }
            }
        }

        $this->loadClassMap($stage_name);
        return true;
    }

    /**
     * @param string $stage_name
     *
     * @return bool
     */
    public function loadClassMap($stage_name = 'default')
    {
        $classmap_file = ABC::env('DIR_TESTS')
                        .'phpunit'.DS
                        .'abc'.DS
                        .'config'.DS
                        .$stage_name.DS
                        .'classmap.php';
        if (is_file($classmap_file)) {
            $class_map = @include_once($classmap_file);
            if($class_map){
                foreach($class_map as $alias=>$name){
                    ABC::addClassToMap($alias, $name);
                }

            }
        }


        $ext_dirs = glob(
            ABC::env('DIR_APP').DS
            .'extensions'.DS
            .'*'.DS
            .'tests'.DS
            .'abc'.DS
            .'config'.DS
            .$stage_name.DS
        );
        foreach ($ext_dirs as $cfg_dir) {
            $classmap_file = $cfg_dir.DS.'classmap.php';
            if (is_file($classmap_file)) {
                $ext_classmap = @include_once($classmap_file);
                if (is_array($ext_classmap)) {
                    foreach($ext_classmap as $alias=>$name){
                        ABC::addClassToMap($alias, $name);
                    }
                }
            }
        }

        return true;
    }

}

/**
 * Class ABCTestCase Base testcase class
 *
 * @package abantecart\tests
 *
 * @property ACustomer $customer
 * @property AResponse $response
 */
class ABCTestCase extends \PHPUnit\Framework\TestCase
{
    protected $registry;

    public function __construct()
    {
        parent::__construct();


        // Registry
        $this->registry = Registry::getInstance();

        //add admin in scope
        $this->registry->set('request', new ARequest());
        $this->registry->get('session')->data['user_id'] = 1;
        $this->registry->set('user', new AUser($this->registry));
    }

    public function __get($key)
    {
        return $this->registry->get($key);
    }

    public function __set($key, $value)
    {
        $this->registry->set($key, $value);
    }

    public function customerLogin($user, $password)
    {
        $logged = $this->customer->login($user, $password);
        if (!$logged) {
            throw new Exception('Could not login customer');
        }
    }

    public function customerLogout()
    {
        if ($this->customer->isLogged()) {
            $this->customer->logout();
        }
    }

    public function getOutput()
    {
        $class = new ReflectionClass("Response");
        $property = $class->getProperty("output");
        $property->setAccessible(true);
        return $property->getValue($this->response);
    }
}