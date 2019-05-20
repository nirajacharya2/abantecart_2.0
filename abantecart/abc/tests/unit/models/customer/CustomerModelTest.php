<?php
namespace abc\tests\unit;

use abc\models\customer\Customer;

/**
 * Class CustomerModelTest
 */
class CustomerModelTest extends ATestCase{


    protected function setUp(){
        //init
    }

    public function testUpdate()
    {
        /**
         * @var Customer $customer
         */
        $customer = Customer::find(12);
        $customer->update(['sms' => '123456789']);
        $this->assertEquals('123456789', $customer->sms);
        $customer->update(['sms' => '']);

    }

    public function testGetCustomers(){

        $total = Customer::getCustomers([],'total_only');
        $this->assertIsInt($total);
        $this->assertEquals(12, $total);

        $rows = Customer::getCustomers();
        $total = $rows->count();
        $this->assertEquals(12, $total);
        $this->assertEquals(12, $rows[0]['total_num_rows']);

        //not approved
        $total = Customer::getCustomers(['filter' => ['approved'=>0]], 'total_only');
        $this->assertEquals(2, $total);

        //by name (first name  and last name)
        $total = Customer::getCustomers(['filter' => ['name'=> 'a' ]], 'total_only');
        $this->assertEquals(10, $total);

        //by email & name (first name  and last name and email)
        $total = Customer::getCustomers(['filter' => ['name_email'=> 'al' ]], 'total_only');
        $this->assertEquals(1, $total);

        //by loginname that starts from
        $total = Customer::getCustomers(['filter' => ['loginname'=> 'b' ]], 'total_only');
        $this->assertEquals(2, $total);

        //by first name that starts from
        $total = Customer::getCustomers(['filter' => ['firstname'=> 'c' ]], 'total_only');
        $this->assertEquals(1, $total);

        //by last name that starts from
        $total = Customer::getCustomers(['filter' => ['lastname'=> 'c' ]], 'total_only');
        $this->assertEquals(2, $total);

        //by email that contains
        $total = Customer::getCustomers(['filter' => ['email'=> '.com' ]], 'total_only');
        $this->assertEquals(12, $total);

        //by emails by list
        $total = Customer::getCustomers(['filter' => [
                                            'email'=> [
                                                    'allenwaters@abantecart.com',
                                                    'anthonyblair@abantecart.com',
                                                    'nonexists'
                                                ]
                                            ]
                                        ], 'total_only');
        $this->assertEquals(2, $total);

        //by phone that contains
        $total = Customer::getCustomers(['filter' => ['telephone'=> '500' ]], 'total_only');
        $this->assertEquals(3, $total);

        //by mobile phone that contains
        $total = Customer::getCustomers(['filter' => ['sms'=> '444' ]], 'total_only');
        $this->assertEquals(1, $total);

        //by customer group id
        $total = Customer::getCustomers(['filter' => ['customer_group_id'=> '1' ]], 'total_only');
        $this->assertEquals(11, $total);

        //all newsletters subscribers (customers by sign or customer group)
        $total = Customer::getCustomers(['filter' => [ 'all_subscribers' => 1 ]], 'total_only');
        $this->assertEquals(2, $total);

        //only newsletters subscribers (by customer group)
        $total = Customer::getCustomers(['filter' => [ 'only_subscribers' => 1 ]], 'total_only');
        $this->assertEquals(1, $total);

        //only newsletters subscribers (customers by sign or customer group)
        $total = Customer::getCustomers(['filter' => [ 'only_customers' => 1 ]], 'total_only');
        $this->assertEquals(11, $total);

        //only with mobile phones
        $total = Customer::getCustomers(['filter' => [ 'only_with_mobile_phones' => 1 ]], 'total_only');
        $this->assertEquals(1, $total);

        //only given ids
        $total = Customer::getCustomers(['filter' => [ 'include' => [2,6] ]], 'total_only');
        $this->assertEquals(2, $total);

        //only except given ids
        $total = Customer::getCustomers(['filter' => [ 'exclude' => [2,6] ]], 'total_only');
        $this->assertEquals(10, $total);

        //only except given ids
        $total = Customer::getCustomers(['filter' => [ 'status' => 1 ]], 'total_only');
        $this->assertEquals(11, $total);

        //only except given ids
        $total = Customer::getCustomers(['filter' => [ 'status' => 1 ]], 'total_only');
        $this->assertEquals(11, $total);

    }

    public function testGetCustomersByProduct()
    {
        $result = Customer::getCustomersByProduct(51);
        $this->assertEquals(2, count($result));

        $result = Customer::getCustomersByProduct(66);
        $this->assertEquals(1, count($result));
    }

    public function testIsUniqueLoginName()
    {

        $result = Customer::isUniqueLoginname('11111111111');
        $this->assertEquals(false, $result);

        $result = Customer::isUniqueLoginname('11111111111', 13);
        $this->assertEquals(true, $result);

        $result = Customer::isUniqueLoginname('11111111111', 12);
        $this->assertEquals(false, $result);
    }
}