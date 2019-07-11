<?php
namespace abc\tests\unit;

use abc\models\order\Order;
use Illuminate\Validation\ValidationException;

/**
 * Class OrderModelTest
 */
class OrderModelTest extends ATestCase{


    protected function setUp(){
        //init
    }

    public function testValidator()
    {
        //validate
        $data = [
                        'order_id' => 'fail',
                        'invoice_id' => -0.000000000123232,
                        'invoice_prefix' => -0.000000000123232,
                        'store_id' => 'fail',
                        'store_name' => -0.000000000123232,
                        'store_url' => -0.000000000123232,
                        'customer_id' => 'fail',
                        'customer_group_id' => 'fail',
                        'firstname' => -0.000000000123232,
                        'lastname' => -0.000000000123232,
                        'telephone' => -0.000000000123232,
                        'fax' => -0.000000000123232,
                        'email' => -0.000000000123232,
                        'shipping_firstname' => -0.000000000123232,
                        'shipping_lastname' => -0.000000000123232,
                        'shipping_company' => -0.000000000123232,
                        'shipping_address_1' => -0.000000000123232,
                        'shipping_address_2' => -0.000000000123232,
                        'shipping_city' => -0.000000000123232,
                        'shipping_postcode' => -0.000000000123232,
                        'shipping_zone' => -0.000000000123232,
                        'shipping_zone_id' => 'fail',
                        'shipping_country' => -0.000000000123232,
                        'shipping_country_id' => 'fail',
                        'shipping_address_format' => -0.000000000123232,
                        'shipping_method' => -0.000000000123232,
                        'shipping_method_key' => -0.000000000123232,
                        'payment_firstname' => -0.000000000123232,
                        'payment_lastname' => -0.000000000123232,
                        'payment_company' => -0.000000000123232,
                        'payment_address_1' => -0.000000000123232,
                        'payment_address_2' => -0.000000000123232,
                        'payment_city' => -0.000000000123232,
                        'payment_postcode' => -0.000000000123232,
                        'payment_zone' => -0.000000000123232,
                        'payment_zone_id' => 'fail',
                        'payment_country' => -0.000000000123232,
                        'payment_country_id' => 'fail',
                        'payment_address_format' => -0.000000000123232,
                        'payment_method' => -0.000000000123232,
                        'payment_method_key' => -0.000000000123232,
                        'comment' => -0.000000000123232,
                        'total' => 'fail',

                        'order_status_id' => 'fail',
                        'language_id' => 'fail',
                        'currency_id' => 'fail',
                        'currency' => 'fail',

                        'value' => 'fail',
                        'coupon_id' => 'fail',
                        'ip' => 'fail',
                        'payment_method_data' => -0,999999999999999,
                    ];
        $product = new Order( $data );
        $errors = [];
        try{
            $product->validate();
        }catch(ValidationException $e){
            $errors = $product->errors()['validation'];
            var_Dump(array_diff(array_keys($data), array_keys($errors) ));
        }

        $this->assertEquals(51, count($errors));




        //check validation of presence in database
        $data = [
            'store_id' => 1500,
            'customer_id' => 1500,
            'order_status_id' => 1500,
            'language_id' => 1500,
            'currency_id' => 1500,
            'currency' => 'UAH',
            'coupon_id' => 1500,
        ];
        $product = new Order( $data );
        $errors = [];
        try{
            $product->validate();
        }catch(ValidationException $e){
            $errors = $product->errors()['validation'];
            var_dump($errors);
        }

        $this->assertEquals(7, count($errors));

        //check validation of presence in database
        $data = [
            'store_id' => 0,
            'customer_id' => null,
            'order_status_id' => 1,
            'language_id' => 1,
            'currency_id' => 1,
            'currency' => 'USD',
            'coupon_id' => null,
        ];
        $product = new Order( $data );
        $errors = [];
        try{
            $product->validate();
        }catch(ValidationException $e){
            $errors = $product->errors()['validation'];
            var_dump($errors);
        }

        $this->assertEquals(0, count($errors));

    }
}