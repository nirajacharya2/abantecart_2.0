<?php

namespace abc\tests\unit;

use abc\models\customer\Address;
use abc\models\customer\Customer;
use abc\models\customer\CustomerTransaction;

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
        $transaction = CustomerTransaction::create(
            [
                'customer_id'      => $customer->customer_id,
                'created_by'       => 1,
                'section'          => 1,
                'credit'           => 0.0001,
                'transaction_type' => 'unit-test',
            ]);
        $customer = Customer::find($address->customer_id);
        $this->assertEquals($now, $customer->date_modified->timestamp);
    }
}