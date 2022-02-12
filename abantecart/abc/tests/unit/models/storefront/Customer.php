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

namespace Tests\unit\models\storefront;

use Tests\unit\models\ATestCase;
use abc\models\customer\Customer;
use Tests\unit\models\modules\listeners\ATestListener;
use PHPUnit\Framework\Warning;

class CustomerTest extends ATestCase
{
    public function testCustomerCreatePassed()
    {
        try {
            $customer = new Customer();
            $customer->fill(
                [
                    'firstname'         => 'Joe',
                    'lastname'          => 'Doe',
                    'loginname'         => 'joedoe',
                    'email'             => 'joedoe@acorncommerce.com',
                    'telephone'         => '1234567890',
                    'approved'          => 0,
                ]
            );
            $customer->save();
            $customerID = $customer->getKey();
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

        return $customerID;
    }

    /**
     * @depends testCustomerCreatePassed
     */
    public function testCustomerLoadPassed($customerID)
    {
        $customer = Customer::find($customerID);
        $this->assertSame($customer->firstname, 'Joe');
        $this->assertSame($customer->lastname, 'Doe');
        $this->assertSame($customer->email, 'joedoe@acorncommerce.com');
    }

    /**
     * @depends testCustomerCreatePassed
     */
    public function testCustomerUpdatePassed($customerID)
    {
        $customer = Customer::find($customerID);
        $customer->telephone = '987654321';
        $customer->save();
        unset($customer);
        $customer = Customer::find($customerID);
        $this->assertSame($customer->telephone, '987654321');
    }

    /**
     * @depends testCustomerCreatePassed
     */
    public function testCustomerApproveFailed($customerID)
    {
        $customer = Customer::find($customerID);
        $customer->approve();
        unset($customer);
        $customer = Customer::find($customerID);
        $this->assertSame($customer->approved, 1);
    }

    /**
     * Clean up
     * @depends testCustomerCreatePassed
     */
    public function testCustomerDeletePassed($customerID)
    {
        $customer = Customer::find($customerID);
        $result = $customer->forceDelete();
        $this->assertEquals(NULL, $result);
    }
}