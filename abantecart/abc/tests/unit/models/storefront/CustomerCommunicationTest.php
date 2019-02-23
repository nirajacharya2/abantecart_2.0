<?php

namespace abc\tests\unit\models\storefront;

use abc\core\lib\ADB;
use abc\models\customer\CustomerCommunication;
use abc\tests\unit\ATestCase;

/**
 * Class CustomerCommunicationTest
 *
 * @package abantecart\tests
 * @property ADB $db
 */

class CustomerCommunicationTest extends ATestCase
{

    protected function tearDown()
    {
        //init
    }

    public function testGetCustomerCommunicationById() {
//disable until model fixed
$this->markTestSkipped('must be revisited.');

        $communication = new CustomerCommunication();
        $result = $communication->getCustomerCommunicationById(5);
        $this->assertTrue(is_array($result));
        $result = $communication->getCustomerCommunicationById(0);
        $this->assertTrue(is_array($result));
    }

}
