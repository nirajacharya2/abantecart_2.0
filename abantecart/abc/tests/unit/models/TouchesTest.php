<?php

namespace abc\tests\unit;

use abc\models\customer\Address;
use abc\models\customer\Customer;
use abc\models\customer\CustomerNotification;
use abc\models\customer\CustomerTransaction;
use abc\models\order\Order;
use abc\models\order\OrderDatum;
use abc\models\order\OrderOption;
use abc\models\order\OrderProduct;
use abc\models\order\OrderStatus;
use abc\models\order\OrderStatusDescription;
use abc\models\order\OrderTotal;

/**
 * Class TouchesTest
 */
class TouchesTest extends ATestCase
{

    public function testCustomerTouches()
    {
        $address = Address::find(1);
        $now = time();
        $address->touch();
        $customer = Customer::find($address->customer_id);
        $this->assertEquals($now, $customer->date_modified->timestamp);

        sleep(1);
        $now = time();
        CustomerTransaction::create(
            [
                'customer_id'      => $customer->customer_id,
                'created_by'       => 1,
                'section'          => 1,
                'credit'           => 0.0001,
                'transaction_type' => 'unit-test',
            ]);
        $customer = Customer::find($address->customer_id);
        $this->assertEquals($now, $customer->date_modified->timestamp);

        sleep(1);
        $now = time();
        CustomerNotification::create(
            [
                'customer_id' => $customer->customer_id,
                'sendpoint'   => 'some-test-sendpoint',
                'protocol'    => 'sms',
                'status'      => 0,
            ]);
        $customer = Customer::find($address->customer_id);
        $this->assertEquals($now, $customer->date_modified->timestamp);
    }

    public function testOrderTouches()
    {
        /** @var OrderOption $orderOption */
        $orderOption = OrderOption::where('order_product_id', '>', 0)->first();
        $now = time();
        $orderOption->touch();
        $orderProduct = OrderProduct::find($orderOption->order_product_id);
        $this->assertEquals($now, $orderProduct->date_modified->timestamp);

        $order = Order::find($orderOption->order_id);
        $this->assertEquals($now, $order->date_modified->timestamp);

        sleep(1);
        $now = time();
        OrderDatum::create(
            [
                'order_id' => $orderOption->order_id,
                'type_id'  => 1,
                'data'     => 'unittest@test.test',
            ]
        );
        $order = Order::find($orderOption->order_id);
        $this->assertEquals($now, $order->date_modified->timestamp);

        //order total
        sleep(1);
        $orderTotal = OrderTotal::where('order_id', '=', $orderOption->order_id)->first();
        $now = time();
        $orderTotal->touch();

        $order = Order::find($orderOption->order_id);
        $this->assertEquals($now, $order->date_modified->timestamp);

        sleep(1);
        /** @var OrderStatusDescription $orderStatusDescription */
        $orderStatusDescription = OrderStatusDescription::first();
        $now = time();
        $orderStatusDescription->touch();
        $orderStatus = OrderStatus::find($orderStatusDescription->order_status_id);
        $this->assertEquals($now, $orderStatus->date_modified->timestamp);

    }
}