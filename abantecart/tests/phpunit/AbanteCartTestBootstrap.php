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

use abc\core\engine\Registry;
use abc\core\lib\ARequest;
use abc\core\lib\AUser;
use Exception;
use ReflectionClass;

set_include_path(__DIR__);
define('DS', DIRECTORY_SEPARATOR);

require dirname(__DIR__, 2).DS.'abc'.DS.'core'.DS.'init'.DS.'cli.php';

/**
 * Class AbanteCartTest
 */
class AbanteCartTest extends \PHPUnit\Framework\TestCase
{
    protected $registry;

    public function __construct()
    {
        parent::__construct();
        $GLOBALS['error_descriptions'] = 'ABC v2 PhpUnit Test';

        $dirname = dirname(__FILE__);
        $dirname = dirname($dirname);

        $dirname = dirname($dirname).'/public_html';
        define('ABC_TEST_ROOT_PATH', $dirname);
        define('ABC_TEST_HTTP_HOST', 'travis-ci.org');
        define('ABC_TEST_PHP_SELF', 'abantecart/abantecart_2.0/abantecart/public/index.php');

        $_SERVER['HTTP_HOST'] = ABC_TEST_HTTP_HOST;
        $_SERVER['PHP_SELF'] = ABC_TEST_PHP_SELF;

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

    public function customerLogin($user, $password, $override = false)
    {
        $logged = $this->customer->login($user, $password, $override);
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