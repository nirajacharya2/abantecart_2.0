<?php
namespace abc\tests\unit;
use abc\models\locale\Country;
use Illuminate\Validation\ValidationException;

/**
 * Class CountryModelTest
 */
class CountryModelTest extends ATestCase{


    protected function setUp(){
        //init
    }

    public function testValidator()
    {

        $country = new Country(
            [
                'iso_code_2' => 121111111,
                'iso_code_3' => 11111111,
                'address_format'=>111111111,
                'status'=> 'zeo',
                'sort_order'=> 'fgnbfgnfgn',
            ]
        );
        $errors = [];
        try {
            $country->validate();
        } catch (ValidationException $e) {
            $errors =$country->errors()['validation'];
        }


        $this->assertEquals(5, count($errors));


        $country= new Country(
            [
                'iso_code_2' => 'fd',
                'iso_code_3' => 'fdd',
                'address_format'=>'somestring',
                'status'=> 1,
                'sort_order'=> 2,
            ]
        );
        $errors = [];
        try {
            $country->validate();
        } catch (ValidationException $e) {
            $errors = $country->errors()['validation'];
        }

        $this->assertEquals(0, count($errors));

    }
}