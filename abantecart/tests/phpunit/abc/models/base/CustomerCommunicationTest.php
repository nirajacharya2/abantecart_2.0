<?php

namespace abantecart\tests;

use abc\core\lib\ADB;
use abc\models\base\CustomerCommunication;

/**
 * Class CustomerCommunicationTest
 *
 * @package abantecart\tests
 * @property ADB $db
 */

class CustomerCommunicationTest extends ABCTestCase
{

    protected function tearDown()
    {
        //init
    }
/*
    public function testGetCustomerCommunicationById() {
//disable until model fixed
$this->markTestSkipped('must be revisited.');

        $communication = new CustomerCommunication();
        $result = $communication->getCustomerCommunicationById(5);
        $this->assertTrue(is_array($result));
        $result = $communication->getCustomerCommunicationById(0);
        $this->assertTrue(is_array($result));
    }*/

}
