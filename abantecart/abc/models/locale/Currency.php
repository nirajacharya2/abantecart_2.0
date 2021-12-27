<?php

namespace abc\models\locale;

use abc\core\lib\AException;
use abc\models\BaseModel;
use abc\models\order\Order;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use abc\core\lib\AConnect;
use abc\core\lib\AError;
use ReflectionException;

/**
 * Class Currency
 *
 * @property int $currency_id
 * @property string $title
 * @property string $code
 * @property string $symbol_left
 * @property string $symbol_right
 * @property string $decimal_place
 * @property float $value
 * @property int $status
 * @property Carbon $date_modified
 *
 * @property Collection $orders
 *
 * @package abc\models
 */
class Currency extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'currency_id';
    public $timestamps = false;

    protected $casts = [
        'value'  => 'float',
        'status' => 'int',
    ];

    protected $dates = [
        'date_modified',
    ];

    protected $fillable = [
        'title',
        'code',
        'symbol_left',
        'symbol_right',
        'decimal_place',
        'value',
        'status',
        'date_modified',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'currency_id');
    }

    /*
     * User methods ????? Todo add RBAC to check for user
     */

    /**
     * Return array with list of Currencies
     *
     * @return array|false
     */
    public function getCurrencies(): array
    {
        if (!$this->hasPermission('read')) {
            return false;
        }
        $currency_data = false;
        //$currency_data = $this->cache->pull('localization.currency');

        if ($currency_data === false) {

            $arCurrencies = $this->orderBy('title', 'ASC')->get()->toArray();

            foreach ($arCurrencies as $result) {
                $currency_data = [
                    $result['code'] => [
                        'currency_id'   => $result['currency_id'],
                        'title'         => $result['title'],
                        'code'          => $result['code'],
                        'symbol_left'   => $result['symbol_left'],
                        'symbol_right'  => $result['symbol_right'],
                        'decimal_place' => $result['decimal_place'],
                        'value'         => $result['value'],
                        'status'        => $result['status'],
                        'date_modified' => $result['date_modified'],
                    ]
                ];
            }

            $this->cache->push('localization.currency', $currency_data);
        }

        return $currency_data;
    }

    /**
     * @param string $operation
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
     * @throws ReflectionException
     * @throws AException
     */
    public function updateCurrencies()
    {
        $api_key = $this->config->get('alphavantage_api_key') ? : 'P6WGY9G9LB22GMBJ';

        $base_currency_code = $this->config->get('config_currency');

        $results = $this->where('code', $base_currency_code)
                        ->where('date_modified', '>', date(strtotime('-1 day')))
                        ->get()->toArray();

        foreach ($results as $result) {
            $url = 'https://www.alphavantage.co/query?function=CURRENCY_EXCHANGE_RATE&from_currency='.$base_currency_code
                .'&to_currency='.$result['code'].'&apikey='.$api_key;
            $connect = new AConnect(true);
            $json = $connect->getData($url);
            if (!$json) {
                $msg = 'Currency Auto Updater Warning: Currency rate code '.$result['code'].' not updated.';
                $error = new AError($msg);
                $error->toLog()->toMessages();
                continue;
            }
            if (isset($json["Realtime Currency Exchange Rate"]["5. Exchange Rate"])) {
                $value = (float)$json["Realtime Currency Exchange Rate"]["5. Exchange Rate"];
                $this->where('code', $result['code'])
                     ->update(['value' => $value]);
            } elseif (isset($json['Information'])) {
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
        if (!$new_currency_code || !$new_currency) {
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
