<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2022 Belavier Commerce LLC
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

namespace Tests\unit\models\locale;

use abc\models\locale\Currency;
use Exception;
use Illuminate\Validation\ValidationException;
use Tests\unit\ATestCase;

class CurrencyModelTest extends ATestCase
{

    public function testValidator()
    {

        $currency = new Currency(
            [
                'currency_id' => 0,
                'title' => '',
                'code' => 111,
                'symbol_left' => 111111111,
                'symbol_right' => 111111111,
                'decimal_place' => 66666666666666666,
                'value' => 200,
                9,
                'status' => 'adhefhgesrhgsedfghaerfg'
            ]
        );
        $errors = [];
        try {
            $currency->validate();
        } catch (ValidationException $e) {
            $errors = $currency->errors()['validation'];
        }

        //var_dump($errors);die;
        $this->assertCount(7, $errors);


        $currency = new Currency(
            [
                'currency_id' => 2,
                'title' => 'dfyh',
                'code' => '123',
                'symbol_left' => 'somestring',
                'symbol_right' => 'somestring',
                'decimal_place' => '$',
                'value' => 15.4,
                'status' => 1
            ]
        );
        $errors = [];
        try {
            $currency->validate();
        } catch (ValidationException $e) {
            $errors = $currency->errors()['validation'];
        }

        $this->assertCount(0, $errors);

    }

    /**
     * @return int
     * @throws Exception
     */
    public function testCreateCurrency()
    {
        $currency = new Currency([
            'title' => 'Test currency',
            'code' => 'TCC',
            'symbol_left' => 'L',
            'symbol_right' => '',
            'decimal_place' => '2',
            'value' => '50',
            'status' => 1,
        ]);
        $currency->save();
        $createdId = $currency->currency_id;
        $this->assertIsInt($createdId);
        return $createdId;
    }

    /**
     * @depends testCreateCurrency
     *
     * @param int $createdId
     */
    public function testReadCurrency(int $createdId)
    {
        /** @var Currency $result */
        $result = Currency::find($createdId);
        $this->assertEquals('Test currency', $result->title);
    }

    /**
     * @depends testCreateCurrency
     *
     * @param int $createdId
     */
    public function testUpdateCurrency(int $createdId)
    {
        Currency::find($createdId)->update([
            'code' => 'TCU',
        ]);
        /** @var Currency $result */
        $result = Currency::find($createdId);
        $this->assertEquals('TCU', $result->code);
    }

    /**
     * @depends testCreateCurrency
     *
     * @param int $createdId
     */
    public function testDeleteCurrency(int $createdId)
    {
        $result = Currency::destroy($createdId);
        $this->assertEquals(1, $result);
    }

}