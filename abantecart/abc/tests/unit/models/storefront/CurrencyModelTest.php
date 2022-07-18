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

namespace Tests\unit\models\models;

use abc\core\lib\ADB;
use abc\models\locale\Currency;
use Tests\unit\ATestCase;

/**
 * Class testCurrencyModel
 *
 * @package abantecart\tests
 * @property ADB $db
 */
class testCurrencyModel extends ATestCase
{

    protected function tearDown():void
    {
        //init
    }

    public function testGetCurrencies()
    {
        $currencyInst = new Currency();
        $arCurrencies = $currencyInst->getCurrencies();
        $this->assertArrayHasKey('USD', $arCurrencies);
        $this->assertArrayHasKey('GBP', $arCurrencies);
        $this->assertArrayHasKey('EUR', $arCurrencies);
        $this->assertEquals('US Dollar',$arCurrencies['USD']['title']);
//        $this->assertArraySubset([
//            'currency_id' => 1,
//            'title' => 'US Dollar',
//            'code' => 'USD',
//            'symbol_left' => '$',
//            'symbol_right' => '',
//            'decimal_place' => '2',
//            'value' => 1.0,
//            'status' => 1,
//            ], $arCurrencies['USD']);
    }

}
