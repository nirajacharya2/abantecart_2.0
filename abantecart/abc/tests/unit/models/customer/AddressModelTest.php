<?php
namespace abc\tests\unit;

use abc\models\customer\Address;
use Illuminate\Validation\ValidationException;

/**
 * Class AddressModelTest
 */
class AddressModelTest extends ATestCase{


    protected function setUp(){
        //init
    }

    public function testUpdate()
    {
        /**
         * @var Address $address
         */
        $address = Address::find(1);
        $address->update(['company' => 'abc']);
        $this->assertEquals('abc', $address->company);
        $address->update(['company' => '']);
    }

    public function testValidation()
    {
        $errors = [];
        /**
         * @var Address $address
         */
        $address = new Address(
            [
                'company' => '',
                'firstname' => '',
                'lastname' => '',
                'address_1' => '',
                'address_2' => '',
                'postcode' => '',
                'city' => '',
                'country_id' => '',
                'zone_id' => ''
            ]
        );
        try {
            $address->validate();
        }catch(ValidationException $e){
            $errors = $address->errors()['validation'];
        }

        $this->assertEquals(
            [
             'firstname',
             'lastname',
             'address_1',
             'postcode',
             'city',
             'country_id',
             'zone_id',
            ],
            array_keys($errors));

        $errors = [];

        $address = Address::find(1);

        //success
        try {
            $address->validate(
                [   'customer_id' => 1,
                    'company' => 'abc',
                    'firstname' => 'testfirstname',
                    'lastname' => 'testlastname',
                    'address_1' => 'asasa',
                    'address_2' => 'dsdsds',
                    'postcode' => '12345',
                    'city' => 'Poltava',
                    'country_id' => '12',
                    'zone_id' => '23'
                ]
            );
        }catch(ValidationException $e){
            $errors = $address->errors()['validation'];
        }
        $this->assertEquals(0, count($errors));

        //check nullable zone_id
        $address = new Address();
        $address->fill( ['zone_id' => '0'] );
        $this->assertEquals( null, $address->zone_id);

        $errors = [];
        //try save without customer_id and address_id - expect errors
        try {
            $address = new Address();
            $address->validate(
                [   'company' => 'abc',
                    'firstname' => '',
                    'lastname' => '',
                    'address_1' => '',
                    'address_2' => 'dsdsds',
                    'postcode' => '',
                    'city' => '',
                    'country_id' => '',
                    'zone_id' => ''
                ]
            );
        }catch(ValidationException $e){
            $errors = $address->errors()['validation'];
        }
        $this->assertEquals(7, count($errors));

        $errors = [];
        //try save without customer_id - expect errors
        try {
            $address->validate(
                [
                    'address_id' => '1',
                    'customer_id' => '',
                    'firstname' => 'test',
                    'lastname' => 'test',
                    'address_1' => 'test',
                    'address_2' => 'dsdsds',
                    'postcode' => '1234',
                    'city' => 'Poltava',
                    'country_id' => '12',
                    'zone_id' => '12'
                ]
            );
        }catch(ValidationException $e){
            $errors = $address->errors()['validation'];
        }
        $this->assertEquals(1, count($errors));


        $errors = [];
        //try save without customer_id - expect errors
        try {
            $address->validate(
                [
                    'address_id' => '1',
                    'customer_id' => '',
                    'firstname' => 'test',
                    'lastname' => 'test',
                    'address_1' => 'test',
                    'address_2' => 'dsdsds',
                    'postcode' => '1234',
                    'city' => 'Poltava',
                    'country_id' => '12',
                    'zone_id' => '0'
                ]
            );
        }catch(ValidationException $e){
            $errors = $address->errors()['validation'];
        }
        $this->assertEquals(1, count($errors));


    }
}