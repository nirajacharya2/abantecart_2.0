<?php
namespace abc\tests\unit;

use abc\models\customer\Customer;
use Illuminate\Validation\ValidationException;

/**
 * Class CustomerModelTest
 */
class CustomerModelTest extends ATestCase{


    protected function setUp(){
        //init
    }

    public function testValidator()
    {
        //validate new customer
        $customer = new Customer(
            [
                'store_id' => 'ewewew',
                'loginname' => '1@abantecart.com',
                'firstname' => '',
                'lastname' => '',
                'password' => '12',
                'password_confirmation' => '12',
                'email' => 'error_1@1',
                'telephone' => 'error max_length_1234567890123456789012345678901234567890',
                'fax' => 'error max_length_1234567890123456789012345678901234567890',
                'sms' => 'error max_length_1234567890123456789012345678901234567890',
                'salt' => 'error123456789',
                'wishlist' => 'ab',
                'address_id' => 'asadff'
            ]
        );
        $errors = [];
        try{
            $customer->validate();
        }catch(ValidationException $e){
            $errors = $customer->errors()['validation'];
        }

        $this->assertEquals(11, count($errors));

        //validate with customer_id

        //validate new customer
        $customer = new Customer(
            [   'customer_id' => 1,
                'telephone' => '12345',
                'fax' => '12458',
                'sms' => '1254',
                'salt' => 'salt',
                'wishlist' => ['a','b', 'c'],
                'address_id' => 1
            ]
        );
        $errors = [];
        try{
            $customer->validate();
        }catch(ValidationException $e){
            $errors = $customer->errors()['validation'];
        }

        $this->assertEquals(0, count($errors));

    }

    public function testUpdate()
    {
        /**
         * @var Customer $customer
         */
        $customer = Customer::find(9);
        $customer->update(
            [
                'sms' => '123456789',
                'password' => '1234567890',
                'loginname' => 'unittest',
                'email' => 'unittest@abantecart.com'
            ]
        );
        $this->assertEquals('123456789', $customer->sms);
        $customer->update(['sms' => '']);

        //validation test
        $errors = [];

        try {
            $customer->validate(['loginname' => 'unittest']);
            $customer->validate(['email' => 'unittest@abantecart.com']);
        }catch (ValidationException $e){
            $errors = $customer->errors()['validation'];
            var_Dump($errors);
        }
        $this->assertEquals(0, count($errors));

        try {
            //change customer and try check unique loginname for him
            $customer = Customer::find(8);
            $customer->validate(['loginname' => 'unittest']);
        }catch (ValidationException $e){
            $errors = $customer->errors()['validation'];
        }
        $this->assertEquals(1, count($errors));

    }

    public function testEditCustomerNotifications(){
        /**
         * @var Customer $customer
         */
        $customer = Customer::find(2);
        $customer->editCustomerNotifications(['sms'=>'qwqwqw']);
        $this->assertEquals('qwqwqw', $customer->sms);
        $customer->update(['sms' => '']);
    }


   /* public function testTypeCasting(){
        $customer = Customer::find(12);
        var_dump($customer->cart); exit;
    }*/

    public function testGetCustomers(){
        $total_count = $this->registry->get('db')->table('customers')->get()->count();
        $total = Customer::getCustomers(['mode' => 'total_only']);
        $this->assertIsInt($total);
        $this->assertEquals($total_count, $total);

        $rows = Customer::getCustomers();
        $total = $rows->count();
        $this->assertEquals($total_count, $total);
        $this->assertEquals($total_count, $rows[0]['total_num_rows']);

        //not approved
        $total = Customer::getCustomers(['filter' => ['approved' => 0], 'mode' => 'total_only']);
        $this->assertEquals(2, $total);

        //by name (first name  and last name)
        $total = Customer::getCustomers(['filter' => ['name' => 'a'], 'mode' => 'total_only']);
        $this->assertEquals(10, $total);

        //by email & name (first name  and last name and email)
        $total = Customer::getCustomers(['filter' => ['name_email' => 'al'], 'mode' => 'total_only']);
        $this->assertEquals(1, $total);

        //by loginname that starts from
        $total = Customer::getCustomers(['filter' => ['loginname' => 'b'], 'mode' => 'total_only']);
        $this->assertEquals(11, $total);

        //by loginname that equal to
        $total = Customer::getCustomers([
                'filter' => [
                    'search_operator' => 'equal',
                    'loginname'       => 'b',
                ],
                'mode'   => 'total_only',
            ]
        );
        $this->assertEquals(0, $total);
        //by loginname that equal to
        $total = Customer::getCustomers([
                'filter' => [
                    'search_operator' => 'equal',
                    'loginname'       => '1@abantecart',
                ],
                'mode'   => 'total_only',
            ]
        );
        $this->assertEquals(1, $total);

        //by first name that starts from
        $total = Customer::getCustomers(['filter' => ['firstname' => 'c'], 'mode' => 'total_only']);
        $this->assertEquals(1, $total);

        //by firstname that equal to
        $total = Customer::getCustomers([
                'filter' => [
                    'search_operator' => 'equal',
                    'firstname'       => 'c',
                ],
                'mode'   => 'total_only',
            ]
        );
        $this->assertEquals(0, $total);

        //by firstname that equal to
        $total = Customer::getCustomers([
                'filter' => [
                    'search_operator' => 'equal',
                    'firstname'       => 'Allen',
                ],
                'mode'   => 'total_only',
            ]
        );
        $this->assertEquals(1, $total);

        //by last name that starts from
        $total = Customer::getCustomers(['filter' => ['lastname' => 'c'], 'mode' => 'total_only']);
        $this->assertEquals(2, $total);

        //by firstname that equal to
        $total = Customer::getCustomers([
                'filter' => [
                    'search_operator' => 'equal',
                    'lastname'        => 'c',
                ],
                'mode'   => 'total_only',
            ]
        );
        $this->assertEquals(0, $total);

        //by firstname that equal to
        $total = Customer::getCustomers([
                'filter' => [
                    'search_operator' => 'equal',
                    'lastname'        => 'waters',
                ],
                'mode'   => 'total_only',
            ]
        );
        $this->assertEquals(1, $total);

        //by email that contains
        $total = Customer::getCustomers(['filter' => ['email' => '.com'], 'mode' => 'total_only']);
        $this->assertEquals(11, $total);

        //by emails by list
        $total = Customer::getCustomers([
            'filter' => [
                'email' => [
                    'allenwaters@abantecart.com',
                    'anthonyblair@abantecart.com',
                    'nonexists',
                ],
            ],
            'mode'   => 'total_only',
        ]);
        $this->assertEquals(2, $total);

        //by email that equal to
        $total = Customer::getCustomers([
                'filter' => [
                    'search_operator' => 'equal',
                    'email'           => 'allenwaters@abantecart.com',
                ],
                'mode'   => 'total_only',
            ]
        );
        $this->assertEquals(1, $total);
        //by email that equal to
        $total = Customer::getCustomers([
                'filter' => [
                    'search_operator' => 'equal',
                    'email'           => 'nonexists',
                ],
                'mode'   => 'total_only',
            ]
        );
        $this->assertEquals(0, $total);
        //by email that equal to
        $total = Customer::getCustomers([
                'filter' => [
                    'search_operator' => 'equal',
                    'email'           => ['1@abantecart', 'allenwaters@abantecart.com'],
                ],
                'mode'   => 'total_only',
            ]
        );
        $this->assertEquals(2, $total);

        //by phone that contains
        $total = Customer::getCustomers(['filter' => ['telephone' => '500'], 'mode' => 'total_only']);
        $this->assertEquals(3, $total);

        //by mobile phone that contains
        $total = Customer::getCustomers(['filter' => ['sms' => '444'], 'mode' => 'total_only']);
        $this->assertEquals(1, $total);

        //by customer group id
        $total = Customer::getCustomers(['filter' => ['customer_group_id' => '1'], 'mode' => 'total_only']);
        $this->assertEquals(11, $total);

        //all newsletters subscribers (customers by sign or customer group)
        $total = Customer::getCustomers(['filter' => ['all_subscribers' => 1], 'mode' => 'total_only']);
        $this->assertEquals(2, $total);

        //only newsletters subscribers (by customer group)
        $total = Customer::getCustomers(['filter' => ['only_subscribers' => 1], 'mode' => 'total_only']);
        $this->assertEquals(1, $total);

        //only newsletters subscribers (customers by sign or customer group)
        $total = Customer::getCustomers(['filter' => ['only_customers' => 1], 'mode' => 'total_only']);
        $this->assertEquals(11, $total);

        //only with mobile phones
        $total = Customer::getCustomers(['filter' => ['only_with_mobile_phones' => 1], 'mode' => 'total_only']);
        $this->assertEquals(1, $total);

        //only given ids
        $total = Customer::getCustomers(['filter' => ['include' => [2, 6]], 'mode' => 'total_only']);
        $this->assertEquals(2, $total);

        //only except given ids
        $total = Customer::getCustomers(['filter' => ['exclude' => [2, 6]], 'mode' => 'total_only']);
        $this->assertEquals(10, $total);

        //only except given ids
        $total = Customer::getCustomers(['filter' => ['status' => 1], 'mode' => 'total_only']);
        $this->assertEquals(11, $total);

        //only except given ids
        $total = Customer::getCustomers(['filter' => ['status' => 1], 'mode' => 'total_only']);
        $this->assertEquals(11, $total);

        //check password
        $account = Customer::getCustomers(
            [
                'filter' => [
                    'search_operator' => 'equal',
                    'loginname'=> 'unittest',
                    'password' => '1234567890'
                ],
                'limit'=> 1 ]);
        $this->assertEquals(1, count($account));
        $this->assertEquals(9, $account->first()->customer_id);
    }

    public function testGetCustomersByProduct()
    {
        $result = Customer::search(['filter' => ['product_id' => 51]]);
        $this->assertEquals(2, count($result));

        $result = Customer::search(['filter' => ['product_id' => 66]]);
        $this->assertEquals(1, count($result));
    }

    public function testIsUniqueLoginName()
    {
        $result = Customer::isUniqueLoginname('1@abantecart');
        $this->assertEquals(false, $result);

        $result = Customer::isUniqueLoginname('1@abantecart', 12);
        $this->assertEquals(true, $result);

        $result = Customer::isUniqueLoginname('1@abantecart', 11);
        $this->assertEquals(false, $result);
    }

}