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

namespace abc\models\locale;

use abc\core\engine\Registry;
use abc\core\lib\AException;
use abc\models\BaseModel;
use abc\models\order\Order;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use abc\core\lib\AConnect;
use abc\core\lib\AError;
use Psr\SimpleCache\InvalidArgumentException;

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
        'value'         => 'float',
        'status'        => 'int',
        'date_modified' => 'datetime'
    ];

    protected $fillable = [
        'currency_id',
        'title',
        'code',
        'symbol_left',
        'symbol_right',
        'decimal_place',
        'value',
        'status',

    ];
    protected $rules = [
        'currency_id'   => [
            'checks'   => [
                'integer',
                'required',
                'sometimes',
                'min:1'
            ],
            'messages' => [
                'integer'  => [
                    'language_key'   => 'error_currency_id',
                    'language_block' => 'localisation/currency',
                    'default_text'   => 'currency_id must be integer!',
                    'section'        => 'admin'
                ],
                'required' => [
                    'language_key'   => 'error_currency_id',
                    'language_block' => 'localisation/currency',
                    'default_text'   => 'currency_id required!',
                    'section'        => 'admin'
                ],
                'min'      => [
                    'language_key'   => 'error_currency_id',
                    'language_block' => 'localisation/currency',
                    'default_text'   => 'currency_id must be more 1!',
                    'section'        => 'admin'
                ],
            ],
        ],
        'title'         => [
            'checks'   => [
                'string',
                'required',
                'sometimes',
                'min:2',
                'max:32'
            ],
            'messages' => [
                'min'      => [
                    'language_key'   => 'error_title',
                    'language_block' => 'localisation/currency',
                    'default_text'   => 'Title must be more 2!',
                    'section'        => 'admin'
                ],
                'max'      => [
                    'language_key'   => 'error_title',
                    'language_block' => 'localisation/currency',
                    'default_text'   => 'Title must not exceed 32 characters!',
                    'section'        => 'admin'
                ],
                'string'   => [
                    'language_key'   => 'error_title',
                    'language_block' => 'localisation/currency',
                    'default_text'   => 'Title must be string!',
                    'section'        => 'admin'
                ],
                'required' => [
                    'language_key'   => 'error_title',
                    'language_block' => 'localisation/currency',
                    'default_text'   => 'Title required!',
                    'section'        => 'admin'
                ],
            ],
        ],
        'code'          => [
            'checks'   => [
                'string',
                'required',
                'size:3',
                'sometimes'
            ],
            'messages' => [
                'max'      => [
                    'language_key'   => 'error_code',
                    'language_block' => 'localisation/currency',
                    'default_text'   => 'code must not exceed 3 characters!',
                    'section'        => 'admin'
                ],
                'string'   => [
                    'language_key'   => 'error_code',
                    'language_block' => 'localisation/currency',
                    'default_text'   => 'code must be string!',
                    'section'        => 'admin'
                ],
                'required' => [
                    'language_key'   => 'error_code',
                    'language_block' => 'localisation/currency',
                    'default_text'   => 'code required!',
                    'section'        => 'admin'
                ],
            ],
        ],
        'symbol_left'   => [
            'checks'   => [
                'string',
                'sometimes',
                'min:1',
                'max:12'
            ],
            'messages' => [
                'string' => [
                    'language_key'   => 'error_symbol_left',
                    'language_block' => 'localisation/currency',
                    'default_text'   => 'Symbol left must be string!',
                    'section'        => 'admin'
                ],
                'min'    => [
                    'language_key'   => 'error_symbol_left',
                    'language_block' => 'localisation/currency',
                    'default_text'   => 'Symbol left must be more 1!',
                    'section'        => 'admin'
                ],
                'max'    => [
                    'language_key'   => 'error_symbol_left',
                    'language_block' => 'localisation/currency',
                    'default_text'   => 'Symbol left must be not exceed 12 characters!',
                    'section'        => 'admin'
                ]
            ]
        ],
        'symbol_right'  => [
            'checks'   => [
                'string',
                'min:1',
                'max:12'
            ],
            'messages' => [
                'string' => [
                    'language_key'   => 'error_symbol_right',
                    'language_block' => 'localisation/currency',
                    'default_text'   => 'Symbol left must be string!',
                    'section'        => 'admin'
                ],
                'min'    => [
                    'language_key'   => 'error_symbol_right',
                    'language_block' => 'localisation/currency',
                    'default_text'   => 'Symbol left must be more 1!',
                    'section'        => 'admin'
                ],
                'max'    => [
                    'language_key'   => 'error_symbol_right',
                    'language_block' => 'localisation/currency',
                    'default_text'   => 'Symbol left must be not exceed 12 characters!',
                    'section'        => 'admin'
                ]
            ]
        ],
        'decimal_place' => [
            'checks'   => [
                'string',
                'max:1'
            ],
            'messages' => [
                'string' => [
                    'language_key'   => 'error_decimal_place',
                    'language_block' => 'localisation/currency',
                    'default_text'   => 'Symbol left must be string!',
                    'section'        => 'admin'
                ],
                'max'    => [
                    'language_key'   => 'error_decimal_place',
                    'language_block' => 'localisation/currency',
                    'default_text'   => 'Decimal place must be more 1 characters',
                    'section'        => '??'
                ]
            ]
        ],
        'value'         => [
            'checks'   => [
                'min:0',
                'max:15,8'
            ],
            'messages' => [
                'min' => [
                    'language_key'  => 'error_value',
                    'language_blog' => 'localisation/currency',
                    'default_text'  => 'Value must be more 0!',
                    'section'       => 'admin'
                ],
                'max' => [
                    'language_key'  => 'error_value',
                    'language_blog' => 'localisation/currency',
                    'default_text'  => 'Value must be not exceed 18.8 characters!',
                    'section'       => 'admin'
                ]
            ]
        ],
        'status'        => [
            'checks'   => [
                'integer',
            ],
            'messages' => [
                'integer' => [
                    'language_key'   => 'error_currency_status',
                    'language_block' => 'localisation/currency',
                    'default_text'   => 'status is not integer!',
                    'section'        => 'admin'
                ]
            ]
        ],
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
     * @throws InvalidArgumentException
     */
    public function getCurrencies()
    {
        if (!$this->hasPermission('read')) {
            return false;
        }
        $cacheKey = 'localization.currency';
        $currency_data = Registry::cache()->get($cacheKey);

        if ($currency_data === null) {
            $arCurrencies = $this->orderBy('title', 'ASC')->get()->toArray();
            foreach ($arCurrencies as $result) {
                $currency_data[$result['code']] = $result;
            }
            Registry::cache()->put($cacheKey, $currency_data);
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
     * @throws AException
     */
    public function updateCurrencies()
    {
        $api_key = Registry::config()->get('alphavantage_api_key')
            ? Registry::config()->get('alphavantage_api_key')
            : 'P6WGY9G9LB22GMBJ';

        $base_currency_code = Registry::config()->get('config_currency');

        $results = $this->where('code', $base_currency_code)
            ->where('date_modified', '>', date(strtotime('-1 day')))
            ->get()->toArray();

        foreach ($results as $result) {
            $url = 'https://www.alphavantage.co/query'
                . '?function=CURRENCY_EXCHANGE_RATE&from_currency=' . $base_currency_code
                . '&to_currency=' . $result['code'] . '&apikey=' . $api_key;
            $connect = new AConnect(true);
            $json = $connect->getData($url);
            if (!$json) {
                $msg = 'Currency Auto Updater Warning: Currency rate code ' . $result['code'] . ' not updated.';
                $error = new AError($msg);
                $error->toLog()->toMessages();
                continue;
            }
            if (isset($json["Realtime Currency Exchange Rate"]["5. Exchange Rate"])) {
                $value = (float)$json["Realtime Currency Exchange Rate"]["5. Exchange Rate"];
                $this->where('code', $result['code'])
                    ->update(['value' => $value]);
            } elseif (isset($json['Information'])) {
                $msg = 'Currency Auto Updater Info: ' . $json['Information'];
                $error = new AError($msg);
                $error->toLog()->toMessages();
            }
            usleep(500);
        }
        $this->where('code', $base_currency_code)
            ->update(['value' => '1.00000']);
        Registry::cache()->flush('localization');
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
