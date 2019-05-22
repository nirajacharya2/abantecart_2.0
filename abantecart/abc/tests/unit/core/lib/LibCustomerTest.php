<?php
namespace abc\tests\unit;

use abc\core\lib\ACustomer;
use abc\models\customer\Customer;

/**
 * Class LibCustomerTest
 */
class LibCustomerTest extends ATestCase{


    public function testCreateCustomer(){

        //create subscriber account with the same email to check if it will be deleted
        $data = [
            'store_id' => 0,
            'loginname' => 'test_subscriber',
            'firstname' => 'test firstname',
            'lastname' => 'test lastname',
            'email'    => 'test_tmp@abantecart.com',
            'telephone' => '1234567890',
            'fax' => '0987654321',
            'password' => 'test',
            'newsletter' => 1,
            'customer_group_id' => Customer::getSubscribersGroupId(),
            'approved' => 1,
            'status'  => 1,
            'ip' => '127.0.0.1',
            'data' => ['some_data' => [1,2,3]],
            'company' => 'abcTests',
            'address_1'   => 'some test address1',
            'address_2'   => 'some test address2',
            'city'        => 'some test city',
            'postcode'    => '0000000',
            'country_id'  => 223,
            'zone_id'     => 3616
        ];
        ACustomer::createCustomer($data);

        $data = [
            'store_id' => 0,
            'loginname' => 'test_loginname',
            'firstname' => 'test firstname',
            'lastname' => 'test lastname',
            'email'    => 'test_tmp@abantecart.com',
            'telephone' => '1234567890',
            'fax' => '0987654321',
            'password' => 'test',
            'newsletter' => '1',
            'customer_group_id' => '1',
            'approved' => 1,
            'status'  => 1,
            'ip' => '127.0.0.1',
            'data' => ['some_data' => [1,2,3]],
            'company' => 'abcTests',
            'address_1'   => 'some test address1',
            'address_2'   => 'some test address2',
            'city'        => 'some test city',
            'postcode'    => '0000000',
            'country_id'  => 223,
            'zone_id'     => 3616
        ];
        $customer_id = ACustomer::createCustomer($data);
        $result = Customer::where('email', '=', 'test_tmp@abantecart.com')->get()->count();
        $this::assertEquals(1, $result);
        //Customer::destroy($customer_id);


    }
}