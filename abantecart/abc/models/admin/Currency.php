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

namespace abc\models\admin;

use abc\core\lib\AConnect;
use abc\core\lib\AError;

class Currency extends \abc\models\base\Currency
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
     * NOTE: Update of currency values works only for default store!
     */
    public function updateCurrencies()
    {
        $api_key = $this->config->get('alphavantage_api_key') ? $this->config->get('alphavantage_api_key') : 'P6WGY9G9LB22GMBJ';

        $base_currency_code =  $this->config->get('config_currency');

        $results = $this->where('code', $base_currency_code)
                      ->where('date_modified', '>', date(strtotime('-1 day')))
                      ->get()->toArray();


        foreach ($results as $result) {
            $url = 'https://www.alphavantage.co/query?function=CURRENCY_EXCHANGE_RATE&from_currency='.$base_currency_code.'&to_currency='.$result['code'].'&apikey='.$api_key;
            $connect = new AConnect(true);
            $json = $connect->getData($url);
            if(!$json){
                $msg = 'Currency Auto Updater Warning: Currency rate code '.$result['code'].' not updated.';
                $error = new AError($msg);
                $error->toLog()->toMessages();
                continue;
            }
            if ( isset( $json["Realtime Currency Exchange Rate"]["5. Exchange Rate"] )) {
                $value = (float)$json["Realtime Currency Exchange Rate"]["5. Exchange Rate"];
                $this->where('code', $result['code'])
                     ->update(['value' => $value]);
            }elseif( isset($json['Information']) ){
                $msg = 'Currency Auto Updater Info: '.$json['Information'];
                $error = new AError($msg);
                $error->toLog()->toMessages();
            }
            usleep(500);
        }
        $this->where('code', $base_currency_code)
            ->update(['value' => '1.00000']);
        $this->cache->remove('localization');

    }

    /**
     * @param string $new_currency_code
     *
     * @return bool
     */
    public function switchConfigCurrency($new_currency_code)
    {
        $new_currency_code = mb_strtoupper(trim($new_currency_code));
        $all_currencies = $this->get()->toArray();
        $new_currency = $all_currencies[$new_currency_code];
        if ( ! $new_currency_code || ! $new_currency) {
            return false;
        }
        $scale = 1 / $new_currency['value'];
        foreach ($all_currencies as $code => $currency) {
            if ($code == $new_currency_code) {
                $new_value = 1.00000;
            } else {
                $new_value = $currency['value'] * $scale;
            }
            $this->find($currency['currency_id'])->update(['value' => $new_value]);
        }

        return true;
    }


}