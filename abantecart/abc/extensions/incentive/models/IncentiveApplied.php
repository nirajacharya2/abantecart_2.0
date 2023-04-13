<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2023 Belavier Commerce LLC
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

namespace abc\extensions\incentive\models;

use abc\core\engine\Registry;
use abc\models\BaseModel;
use abc\models\casts\Json;
use abc\models\customer\Customer;
use abc\models\QueryBuilder;
use Carbon\Carbon;
use Illuminate\Database\Query\JoinClause;

/**
 * Class IncentiveApplied
 *
 * @property int $incentive_id
 * @property int $customer_id
 * @property int $result_code
 * @property string $result
 * @property Carbon $date_added
 *
 * @package abc\models
 */
class IncentiveApplied extends BaseModel
{
    protected $primaryKey = 'id';
    public $timestamps = false;
    public $table = 'incentive_applied';

    protected $casts = [
        'incentive_id' => 'int',
        'customer_id'  => 'int',
        'result_code'  => 'bool',
        'result'       => Json::class,
        'bonus_amount' => 'float',
    ];

    protected $dates = [
        'date_added',
    ];

    protected $fillable = [
        'incentive_id',
        'customer_id',
        'result_code',
        'result',
        'bonus_amount'
    ];

    protected $rules = [
        'incentive_id' => [
            'checks'   => [
                'integer',
                'required'
            ],
            'messages' => [
                '*' => ['default_text' => 'Incentive ID is not Integer!'],
            ],
        ],
        'customer_id'  => [
            'checks'   => [
                'integer',
                'required'
            ],
            'messages' => [
                '*' => ['default_text' => 'Incentive ID is not Integer!'],
            ],
        ],
        'result_code'  => [
            'checks'   => [
                'boolean',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'Result Code must be 1 or 0 !',
                ],
            ],
        ],
        'result'       => [
            'checks'   => [
                'string',
                'max:1500',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be a string less than 1500 characters!',
                ],
            ],
        ]
    ];

    public function incentive()
    {
        return $this->belongsTo(Incentive::class, 'incentive_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * @param int $incentiveId
     * @param int $customerId
     * @param string|null $conditionKey
     * @param string $idx
     * @param array $extra - some additional array for hooks
     * @return int
     */
    public static function getAppliedCount(
        int     $incentiveId,
        int     $customerId,
        ?string $conditionKey = '',
                $idx = '',
                $extra = []
    )
    {
        $output = 0;
        if (!$incentiveId || !$customerId) {
            return false;
        }
        $query = static::where('incentive_id', '=', $incentiveId)
            ->where('customer_id', '=', $customerId)
            //successfully applied only
            ->where('result_code', '=', 0);
        //allow to extend this method from extensions
        Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $query, func_get_args());

        $rows = $query->get();
        if (!$conditionKey) {
            return $rows->count();
        }

        foreach ($rows as $row) {
            if (in_array($conditionKey . ':' . $idx, array_keys((array)$row->result['matched_conditions']))) {
                $output++;
            }
        }

        return $output;
    }

    public static function getItems(array $params)
    {
        $params['language_id'] = $params['language_id'] ?: static::$current_language_id;
        $params['sort'] = $params['sort'] ?? 'incentive_applied.date_added';
        $params['order'] = $params['order'] ?? 'DESC';
        $params['start'] = max($params['start'], 0);
        $params['limit'] = abs((int)$params['limit']) ?: 20;
        $filter = (array)$params['filter'];

        //override to use prepared version of filter inside hooks
        $params['filter'] = $filter;

        $db = Registry::db();

        $table = $db->table_name('incentive_applied');
        $idescTable = $db->table_name('incentive_descriptions');
        $customerTable = $db->table_name('customers');

        $query = self::selectRaw(Registry::db()->raw_sql_row_count() . ' ' . $idescTable . '.*');
        $query->addSelect('incentive_applied.*');
        $query->selectRaw("CONCAT( " . $customerTable . ".firstname, ' ', " . $customerTable . ".lastname) as customer_name");

        $query->leftJoin(
            'customers',
            'incentive_applied.customer_id',
            '=',
            'customers.customer_id'
        );

        $query->leftJoin(
            'incentive_descriptions',
            function ($join) use ($params) {
                /** @var JoinClause $join */
                $join->on('incentive_descriptions.incentive_id', '=', 'incentive_applied.incentive_id')
                    ->where('incentive_descriptions.language_id', '=', $params['language_id']);
            }
        );

        if ($filter['start_date'] || $filter['end_date']) {
            if ($filter['start_date'] instanceof Carbon) {
                $startDate = $filter['start_date'];
            } elseif ($filter['start_date']) {
                $startDate = Carbon::parse($filter['start_date']);
            } else {
                $startDate = Carbon::parse('1970-01-01');
            }
            $startDate = $startDate->startOfDay()->toDateTimeString();

            if ($filter['end_date'] instanceof Carbon) {
                $endDate = $filter['end_date'];
            } elseif ($filter['end_date']) {
                $endDate = Carbon::parse($filter['end_date']);
            } else {
                $endDate = Carbon::now();
            }
            $endDate = $endDate->endOfDay()->toDateTimeString();
            $query->whereDate('incentive_applied.date_added', '>=', $startDate);
            $query->whereDate('incentive_applied.date_added', '<=', $endDate);
        }

        if ($filter['customer_id']) {
            $query->where('customer_id', '=', $filter['customer_id']);
        }
        if ($filter['customer']) {
            $query->where(
                function ($query) use ($filter) {
                    /** @var QueryBuilder $query */
                    return $query->where('customers.lastname', 'like', '%' . $filter['customer'] . '%')
                        ->orWhere('customers.firstname', 'like', '%' . $filter['customer'] . '%')
                        ->orWhere('customers.customer_id', '=', (int)$filter['customer']);
                }
            );
        }
        if ($filter['incentive_id']) {
            $query->where('incentive_applied.incentive_id', '=', $filter['incentive_id']);
        }
        if (isset($filter['bonus_amount'])) {
            $query->where('incentive_applied.bonus_amount', '=', $filter['bonus_amount']);
        }


        //NOTE: order by must be raw sql string
        $sort_data = [
            'incentive_id'  => $idescTable . ".name",
            'customer_name' => "customer_name",
            'date_added'    => $table . ".date_added",
            'result_code'   => $table . ".result_code",
            'bonus_amount'  => $table . ".bonus_amount"
        ];

        $orderBy = $sort_data[$params['sort']] ?: $table . ".date_added";
        if (isset($params['order']) && (strtoupper($params['order']) == 'DESC')) {
            $sorting = "desc";
        } else {
            $sorting = "asc";
        }

        $query->orderByRaw($orderBy . " " . $sorting);

        //pagination
        if (isset($params['start'])) {
            $params['start'] = max(0, $params['start']);
            $query->offset((int)$params['start'])
                ->limit((int)$params['limit']);
        }
        //allow to extend this method from extensions
        Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $query, $params);

        $output = $query->get();
        //add total number of rows into each row
        $totalNumRows = $db->sql_get_row_count();
        $output->total = $totalNumRows;

        return $output;
    }
}