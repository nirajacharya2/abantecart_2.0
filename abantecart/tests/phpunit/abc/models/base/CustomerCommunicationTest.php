<?php
/**
 * Created by PhpStorm.
 * User: denis
 * Date: 25/09/2018
 * Time: 21:47
 */

namespace abantecart\tests;


use abc\core\helper\AHelperUtils;
use abc\core\lib\ADB;
use abc\models\base\CustomerCommunication;
use PHPUnit\Framework\ExpectationFailedException;
use abantecart\tests\AbanteCartTest;

/**
 * Class CustomerCommunicationTest
 *
 * @package abantecart\tests
 * @property ADB $db
 */

class CustomerCommunicationTest extends AbanteCartTest
{

    protected function tearDown()
    {
        //init
    }
/*
    public function testGetCustomerCommunicationById() {
        $communication = new CustomerCommunication();
        $result = $communication->getCustomerCommunicationById(5);
        $this->assertTrue(is_array($result));
        $result = $communication->getCustomerCommunicationById(0);
        $this->assertTrue(is_array($result));
    }*/

    /*public function testBasicExample()
    {
        $arColumns = $this->db->table('customer_communications')->columns();
        AHelperUtils::df($arColumns);
        $this->assertTrue(true);
    }*/
}
