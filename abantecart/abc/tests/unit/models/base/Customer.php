<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2018 Belavier Commerce LLC
 *
 * This source file is subject to Open Software License (OSL 3.0)
 * License details is bundled with this package in the file LICENSE.txt.
 * It is also available at this URL:
 * <http://www.opensource.org/licenses/OSL-3.0>
 *
 * UPGRADE NOTE:
 * Do not edit or add to this file if you wish to upgrade AbanteCart to newer
 * versions in the future. If you wish to customize AbanteCart for your
 * needs please refer to http://www.abantecart.com for more information.
 */

namespace unit\models\base;

use abc\tests\unit\ATestCase;
use abc\models\base\Customer;
use abc\tests\unit\modules\listeners\ATestListener;
use PHPUnit\Framework\Warning;

class CustomerTest extends ATestCase
{
    public function testCustomerLoadPassed()
    {
        $customer = Customer::find(10);
        $this->assertSame($customer->firstname, 'Garrison');
        $this->assertSame($customer->lastname, 'Baxter');
    }

    public function testCustomerCreatePassed()
    {
        try {
            $customer = new Customer();
            $customer->fill(
                [
                    'firstname'         => 'First',
                    'lastname'          => 'Last',
                    'loginname'         => 'loginname1',
                    'email'             => 'testing@testing.com',
                    'telephone'         => '1234567890',
                ]
            );
            $customer->save();
            $customer_id = $customer->getKey();
            $result = true;
        } catch (\PDOException $e) {
            $result = false;
        } catch (Warning $e) {
            $result = false;
        } catch (\Exception $e) {
            $result = false;
            $this->fail($e->getMessage());
        }
        $this->assertEquals(true, $result);

    }


}