<?php

namespace abc\tests\unit;

use abc\core\engine\Registry;
use abc\models\catalog\ProductOption;
use abc\models\order\Order;
use abc\models\order\OrderDatum;
use abc\models\order\OrderProduct;
use Illuminate\Validation\ValidationException;

/**
 * Class OrderModelTest
 */
class OrderModelTest extends ATestCase
{

    protected function setUp()
    {
        //init
    }


        public function testValidator()
        {
            //validate
            $data = [
                'order_id'                => 'fail',
                'invoice_id'              => -0.000000000123232,
                'invoice_prefix'          => -0.000000000123232,
                'store_id'                => 'fail',
                'store_name'              => -0.000000000123232,
                'store_url'               => -0.000000000123232,
                'customer_id'             => 'fail',
                'customer_group_id'       => 'fail',
                'firstname'               => -0.000000000123232,
                'lastname'                => -0.000000000123232,
                'telephone'               => -0.000000000123232,
                'fax'                     => -0.000000000123232,
                'email'                   => -0.000000000123232,
                'shipping_firstname'      => -0.000000000123232,
                'shipping_lastname'       => -0.000000000123232,
                'shipping_company'        => -0.000000000123232,
                'shipping_address_1'      => -0.000000000123232,
                'shipping_address_2'      => -0.000000000123232,
                'shipping_city'           => -0.000000000123232,
                'shipping_postcode'       => -0.000000000123232,
                'shipping_zone'           => -0.000000000123232,
                'shipping_zone_id'        => 'fail',
                'shipping_country'        => -0.000000000123232,
                'shipping_country_id'     => 'fail',
                'shipping_address_format' => -0.000000000123232,
                'shipping_method'         => -0.000000000123232,
                'shipping_method_key'     => -0.000000000123232,
                'payment_firstname'       => -0.000000000123232,
                'payment_lastname'        => -0.000000000123232,
                'payment_company'         => -0.000000000123232,
                'payment_address_1'       => -0.000000000123232,
                'payment_address_2'       => -0.000000000123232,
                'payment_city'            => -0.000000000123232,
                'payment_postcode'        => -0.000000000123232,
                'payment_zone'            => -0.000000000123232,
                'payment_zone_id'         => 'fail',
                'payment_country'         => -0.000000000123232,
                'payment_country_id'      => 'fail',
                'payment_address_format'  => -0.000000000123232,
                'payment_method'          => -0.000000000123232,
                'payment_method_key'      => -0.000000000123232,
                'comment'                 => -0.000000000123232,
                'total'                   => 'fail',

                'order_status_id' => 'fail',
                'language_id'     => 'fail',
                'currency_id'     => 'fail',
                'currency'        => 'fail',

                'value'               => 'fail',
                'coupon_id'           => 'fail',
                'ip'                  => 'fail',
                'payment_method_data' => -0,
                999999999999999,
            ];
            $order = new Order($data);
            $errors = [];
            try {
                $order->validate();
            } catch (ValidationException $e) {
                $errors = $order->errors()['validation'];
                // var_Dump(array_diff(array_keys($data), array_keys($errors) ));
            }

            $this->assertEquals(48, count($errors));

            //check validation of presence in database
            $data = [
                'store_id'        => 1500,
                'customer_id'     => 1500,
                'order_status_id' => 1500,
                'language_id'     => 1500,
                'currency_id'     => 1500,
                'currency'        => 'UAH',
                'coupon_id'       => 1500,
            ];
            $order = new Order($data);
            $errors = [];
            try {
                $order->validate();
            } catch (ValidationException $e) {
                $errors = $order->errors()['validation'];
                // var_Dump(array_diff(array_keys($data), array_keys($errors) ));
            }

            $this->assertEquals(7, count($errors));

            //check validation of presence in database
            $data = [
                'store_id'        => 0,
                'customer_id'     => null,
                'order_status_id' => 1,
                'language_id'     => 1,
                'currency_id'     => 1,
                'currency'        => 'USD',
                'coupon_id'       => null,
            ];
            $order = new Order($data);
            $errors = [];
            try {
                $order->validate();
            } catch (ValidationException $e) {
                $errors = $order->errors()['validation'];
                // var_Dump(array_diff(array_keys($data), array_keys($errors) ));
            }

            $this->assertEquals(0, count($errors));

            //check correct value
            $data = [
                'order_id'                => 10000,
                'invoice_id'              => 12,
                'invoice_prefix'          => 'PRE-',
                'store_id'                => 0,
                'store_name'              => 'Test Store',
                'store_url'               => 'http://localhost/public/',
                'customer_id'             => 2,
                'customer_group_id'       => 1,
                'firstname'               => 'TestName',
                'lastname'                => 'TestLast',
                'telephone'               => '+38098123456788',
                'fax'                     => '+38098123456788',
                'email'                   => 'unittest@abantecart.com',
                'shipping_firstname'      => 'TestShippingName',
                'shipping_lastname'       => 'TestShippingLastName',
                'shipping_company'        => 'Abc2.0',
                'shipping_address_1'      => 'Somewhere1',
                'shipping_address_2'      => 'Somewhere Street',
                'shipping_city'           => 'New York',
                'shipping_postcode'       => '11222',
                'shipping_zone'           => 'Manhattan',
                'shipping_zone_id'        => 1,
                'shipping_country'        => 'USA',
                'shipping_country_id'     => 1,
                'shipping_address_format' => 'blablabla',
                'shipping_method'         => 'free_shipping.free_shipping',
                'shipping_method_key'     => 'free_shipping',
                'payment_firstname'       => 'TestShippingName',
                'payment_lastname'        => 'TestShippingLastName',
                'payment_company'         => 'Abc2.0',
                'payment_address_1'       => 'Somewhere2',
                'payment_address_2'       => 'Somewhere Street2',
                'payment_city'            => 'Poltava',
                'payment_postcode'        => '123456',
                'payment_zone'            => 'Poltava',
                'payment_zone_id'         => 2,
                'payment_country'         => 'Ukraine',
                'payment_country_id'      => 2,
                'payment_address_format'  => 'blablabla222',
                'payment_method'          => 'cod',
                'payment_method_key'      => 'cod',
                'comment'                 => 'unittest',
                'total'                   => 1.0,

                'order_status_id' => 1,
                'language_id'     => 1,
                'currency_id'     => 1,
                'currency'        => 'USD',

                'value'               => 1.0,
                'coupon_id'           => null,
                'ip'                  => '127.0.0.1',
                'payment_method_data' => ['some_payment' => 'some data'],
            ];

            $order = new Order($data);
            $errors = [];
            $order_id = null;
            try {
                $order->validate();
                $order->save();
            } catch (ValidationException $e) {
                $errors = $order->errors()['validation'];
                var_Dump(array_intersect_key($data, $errors));
                var_Dump($errors);
            }catch(\Exception $e){

            }

            $this->assertEquals(0, count($errors));

            if ($order->order_id) {
                //Order::destroy($order->order_id);
            }

        }

    public function testStatic()
    {

        $order_id = 9;
        $customer_id = 8;
        $order_ims = [
            1 => [
                'uri'    => 'test@abc2.com',
                'status' => 1,
            ],
            2 => [
                'uri'    => '0000000000',
                'status' => 1,
            ],
        ];

        foreach ($order_ims as $type_id => $data) {
            $oData = new OrderDatum(['order_id' => $order_id, 'type_id' => $type_id, 'data' => $data]);
            $oData->save();
        }

        $settings = Registry::config();
        $settings->set('config_im_guest_email_status', 1);
        $settings->set('config_im_guest_sms_status', 1);

        $result = Order::getImFromOrderData($order_id, $customer_id);
        $this->assertEquals($order_ims[2]['uri'], $result['sms']['uri']);

        $option = ProductOption::getProductOptionsByIds([304])->first();

        $this->assertNotNull($option->product_option_value_id);
        $this->assertEquals('Pink Pool', $option->option_value_name);

        $order = Order::find(6);
        $customer_id = $order->customer_id;
        $order->update(['customer_id' => null]);

        $product = OrderProduct::where('order_id', '=', 6)->first();

        $results = Order::getGuestOrdersWithProduct($product->product_id)->toArray();
        $order->update(['customer_id' => $customer_id]);
        $this->assertEquals(31, $results[0]['order_product_id']);

        //TODO: write test Order::getOrders()
        //TODO: write test Order::getOrdersArray()



    }
}