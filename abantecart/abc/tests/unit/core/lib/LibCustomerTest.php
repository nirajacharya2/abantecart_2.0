<?php
namespace Tests\unit\core\lib;

use abc\core\engine\Registry;
use abc\core\lib\ACustomer;
use abc\models\customer\Customer;
use Exception;
use Illuminate\Validation\ValidationException;
use Tests\unit\ATestCase;

/**
 * Class LibCustomerTest
 */
class LibCustomerTest extends ATestCase{

    public function testValidateRegistration()
    {
        Registry::config()->set('prevent_email_as_login', 1);
        //set agreement with policy rules
        Registry::config()->set('config_account_id', 1);
        $data = [
            'loginname' => '1@abantecart.com',
            'firstname' => '',
            'lastname' => '',
            'password' => '&nbsp',
            'password_confirmation' => '&nbs',
            'email' => '1@1',
            'company' => '123456789012345678901234567890123', //33 chars, 32 allowed
            'address_1' => '',
            'address_2' => '',
            'city' => '',
            'postcode' => '',
            'country_id' => 'false',
            'zone_id' => 'false',
        ];
        $errors = ACustomer::validateRegistrationData( $data );

        $this->assertCount(12, $errors);

        $errors = ACustomer::validateSubscribeData(
            [
                'loginname' => '',
                'firstname' => '',
                'lastname' => '',
                'email' => '111@aban',
                //note: password error will be returned because password confirmation absent
                'password' => '12345',
                'password_confirmation' => ''
            ]
        );

        $this->assertCount(6, $errors);

        $errors = ACustomer::validateSubscribeData(
            [
                'store_id' => 0,
                'loginname' => 'eee_loginname',
                'firstname' => 'eeee_firstname',
                'lastname' => 'eeee_lastname',
                'email' => '111@abantecart.com',
                'password' => '12345',
                'password_confirmation' => '12345'
            ]
        );

        $this->assertCount(0, $errors);
    }

    public function testCreateCustomer()
    {
        $loginname = 'test_loginname';
        $password = 'super-secret-password';
        $email = 'test_tmp@abantecart.com';

        Customer::whereIn('loginname', ['test_subscriber', $loginname])
                ->orWhere('email',$email)
                ->forceDelete();

        //create subscriber account with the same email to check if it will be deleted
        $data = [
            'store_id' => 0,
            'loginname' => 'test_subscriber',
            'firstname' => 'test firstname',
            'lastname' => 'test lastname',
            'email'    => $email,
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
        }catch(Exception $e){
            var_dump($e->getMessage());
            exit;
        }

        $data = [
            //'store_id' => 0,
            'loginname' => $loginname,
            'firstname' => 'test firstname',
            'lastname' => 'test lastname',
            'email'    => $email,
            'telephone' => '1234567890',
            'fax' => '0987654321',
            'password' => $password,
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
            $customer_model = ACustomer::createCustomer($data);
            $customer_id = $customer_model->customer_id;
        }catch(ValidationException $e){
            var_dump($e->errors());
            exit;
        }catch(Exception $e){
            var_dump($e->getMessage());
            exit;
        }

        $result = Customer::where('email', '=', $email)->get();
        $this::assertEquals(1, $result->count());

        //check login
        $customer = new ACustomer(Registry::getInstance());
        $result = $customer->login($loginname, $password);
        $this::assertEquals(true, $result);

        $customer = new ACustomer(Registry::getInstance());
        $result = $customer->login($loginname, 'test-non-exists-password');
        $this::assertEquals(false, $result);

        //destroy must remove newly created customer
        Customer::destroy($customer_id);
    }
}