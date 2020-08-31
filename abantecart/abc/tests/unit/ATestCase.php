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

namespace abc\tests\unit;

use abc\core\ABC;
use abc\core\engine\Registry;
use PHPUnit\Framework\TestCase;
use abc\core\lib\ACustomer;
use abc\core\lib\ADB;
use abc\core\lib\ALog;
use abc\core\lib\ARequest;
use abc\core\lib\AResponse;
use abc\core\lib\ASession;
use abc\core\lib\AUser;
use Exception;
use ReflectionClass;

require_once __DIR__.DS.'TestBootstrap.php';

/**
 * Class ATestCase Base test-case class
 *
 * @package abantecart\tests
 *
 * @property ACustomer $customer
 * @property ASession $session
 * @property AResponse $response
 * @property ARequest $request
 * @property ALog $log
 * @property ADB $db
 */
class ATestCase extends TestCase
{
    /**
     * @var Registry
     */
    protected $registry;

    public function __construct()
    {
        parent::__construct();
        //load test bootstrap singleton
        $tb = TestBootstrap::getInstance();
        $this->registry = $tb->registry;
        //add admin to the scope
        if (!$this->registry->get('request')) {
            $this->registry->set('request', new ARequest());
        }
        if (!$this->registry->get('user')) {
            $this->registry->get('session')->data['user_id'] = 1;
            $this->registry->set('user', ABC::getObjectByAlias('AUser', [$this->registry]));
        }
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