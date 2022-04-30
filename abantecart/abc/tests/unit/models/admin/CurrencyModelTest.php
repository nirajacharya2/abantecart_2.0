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

namespace unit\models\admin;

use abc\models\locale\Currency;
use abc\tests\unit\ATestCase;

class CurrencyModelTest extends ATestCase
{

    protected function setUp():void
    {
        //init
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function testCreateCurrency()
    {
        $currency = new Currency([
            'title'         => 'Test currency',
            'code'          => 'TCC',
            'symbol_left'   => 'L',
            'symbol_right'  => '',
            'decimal_place' => '2',
            'value'         => '50',
            'status'        => 1,
        ]);
        $currency->save();
        $createdId = $currency->currency_id;
        $this->assertIsInt($createdId);
        return $createdId;
    }

    /**
     * @depends testCreateCurrency
     *
     * @param $createdId
     *
     * @return mixed
     */
    public function testReadCurrency(int $createdId)
    {
        $result = Currency::find($createdId);
        $this->assertEquals('Test currency', $result->title);
    }

    /**
     * @depends testCreateCurrency
     *
     * @param $createdId
     *
     * @return mixed
     */
    public function testUpdateCurrency(int $createdId)
    {
        Currency::find($createdId)->update([
            'code'  => 'TCU',
        ]);
        $result = Currency::find($createdId);
        $this->assertEquals('TCU', $result->code);
    }

    /**
     * @depends testCreateCurrency
     *
     * @param $createdId
     */
    public function testDeleteCurrency(int $createdId)
    {
        $result = Currency::destroy($createdId);
        $this->assertEquals(1, $result);
    }

}