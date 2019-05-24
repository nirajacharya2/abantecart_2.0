<?php
namespace abc\tests\unit;

use abc\core\engine\Registry;
use abc\core\lib\ACustomer;
use abc\models\customer\Customer;
use Illuminate\Validation\ValidationException;

/**
 * Class LibCustomerTest
 */
class LibCustomerTest extends ATestCase{

    public function testValidateRegistration()
    {
        Registry::config()->set('prevent_email_as_login', 1);
        //set agreement with policy rules
        Registry::config()->set('config_account_id', 1);
        $errors = ACustomer::validateRegistrationData(
            [
                'loginname' => '1@abantecart.com',
                'firstname' => '',
                'lastname' => '',
                'password' => '&nbsp;',
                'confirm' => '***',
                'email' => '1@1',
                'company' => '123456789012345678901234567890123', //33 chars, 32 allowed
                'address_1' => '',
                'address_2' => '',
                'city' => '',
                'postcode' => '',
                'country_id' => 'false',
                'zone_id' => 'false',
            ]
        );
        $this->assertEquals(12, count($errors));

        $errors = ACustomer::validateSubscribeData(
            [
                'loginname' => '',
                'firstname' => '',
                'lastname' => '',
                'email' => '111@aban',
                'password' => '12345'
            ]
        );
        $this->assertEquals(3, count($errors));
        $errors = ACustomer::validateSubscribeData(
            [
                'loginname' => 'eee_loginname',
                'firstname' => 'eeee_firstname',
                'lastname' => 'eeee_lastname',
                'email' => '111@abantecart.com',
                'password' => '12345'
            ]
        );
        $this->assertEquals(0, count($errors));
    }

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
            'password' => 'tesdsdst',
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


        try{
        ACustomer::createCustomer($data);
        }catch(ValidationException $e){
            var_dump($e->errors());
            exit;
        }catch(\Exception $e){
            var_dump($e->getMessage());
            exit;
        }
        $data = [
            //'store_id' => 0,
            'loginname' => 'test_loginname',
            'firstname' => 'test firstname',
            'lastname' => 'test lastname',
            'email'    => 'test_tmp@abantecart.com',
            'telephone' => '1234567890',
            'fax' => '0987654321',
            'password' => 'ytytytest',
            'newsletter' => '1',
            'customer_group_id' => '1',
            'approved' => 1,
            'status'  => 1,
            'ip' => '127.0.0.1',
            'data' => ['some_data' => [1,2,3]],
            'cart' => ['eeee' => [1,2,3]],
            'wishlist' => ['rrrrr' => [1,2,3]],
            'company' => 'abcTests',
            'address_1'   => 'some test address1',
            'address_2'   => 'some test address2',
            'city'        => 'some test city',
            'postcode'    => '0000000',
            'country_id'  => 223,
            'zone_id'     => 3616
        ];
        try{
            $customer_id = ACustomer::createCustomer($data);
        }catch(ValidationException $e){
            var_dump($e->errors());
            exit;
        }catch(\Exception $e){
            var_dump($e->getMessage());
            exit;
        }

       /* $c = Customer::find($customer_id);
        var_dump($c->data);
        var_dump($c->cart);
        var_dump($c->wishlist);*/
        $result = Customer::where('email', '=', 'test_tmp@abantecart.com')->get()->count();
        $this::assertEquals(1, $result);

        //this destroy must remove both newly created customers
        Customer::destroy($customer_id);


    }
}