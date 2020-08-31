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

namespace abc\models\storefront;

class Currency extends \abc\models\locale\Currency
{
    /**
     * @param $operation
     *
     * @param array $columns
     *
     * @return bool
     */
    public function hasPermission(string $operation, array $columns = ['*']): bool
    {
        return true;
    }

    /**
     * Return array with list of Currencies
     *
     * @return array
     */
    public function getCurrencies(): array
    {
        if (!$this->hasPermission('read')) {
            return false;
        }
        $currency_data = null;
        //????
        //$currency_data = $this->cache->get('localization.currency');

        if ($currency_data === null) {

            $arCurrencies = $this->orderBy('title', 'ASC')->get()->toArray();

            foreach ($arCurrencies as $result) {
                $currency_data[$result['code']] = [
                    'currency_id'   => $result['currency_id'],
                    'title'         => $result['title'],
                    'code'          => $result['code'],
                    'symbol_left'   => $result['symbol_left'],
                    'symbol_right'  => $result['symbol_right'],
                    'decimal_place' => $result['decimal_place'],
                    'value'         => $result['value'],
                    'status'        => $result['status'],
                    'date_modified' => $result['date_modified']
                ];
            }

            $this->cache->put('localization.currency', $currency_data);
        }

        return $currency_data;
    }

}