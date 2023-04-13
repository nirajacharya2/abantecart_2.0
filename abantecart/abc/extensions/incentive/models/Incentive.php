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

use abc\core\ABC;
use abc\core\engine\Registry;
use abc\core\lib\BaseIncentiveCondition;
use abc\core\lib\CheckoutBase;
use abc\extensions\incentive\modules\conditions\CustomerCountry;
use abc\models\BaseModel;
use abc\models\casts\Html;
use abc\models\casts\Json;
use abc\models\casts\Serialized;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\JoinClause;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Class Incentive
 *
 * @property int $incentive_id
 * @property array $conditions
 * @property array $bonuses
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property int $priority
 * @property int $stop
 * @property string $incentive_type
 * @property string $conditions_hash
 * @property int $status
 * @property int $resource_id
 * @property int $limit_of_usages
 * @property int $number_of_usages
 * @property string $user_conditions_hash
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @property Collection $descriptions
 *
 * @package abc\models
 */
class Incentive extends BaseModel
{
    protected $cascadeDeletes = ['descriptions', 'applied'];
    protected $primaryKey = 'incentive_id';

    protected $casts = [
        'conditions'           => Serialized::class,
        'bonuses'              => Serialized::class,
        'start_date'           => 'datetime',
        'end_date'             => 'datetime',
        'priority'             => 'int',
        'stop'                 => 'bool',
        'incentive_type'       => 'string',
        'conditions_hash'      => 'string',
        'status'               => 'bool',
        'resource_id'          => 'int',
        'limit_of_usages'      => 'int',
        'number_of_usages'     => 'int',
        'user_conditions_hash' => 'string',
        'date_added'           => 'datetime',
        'date_modified'        => 'datetime'
    ];

    protected $fillable = [
        'conditions',
        'bonuses',
        'start_date',
        'end_date',
        'priority',
        'stop',
        'incentive_type',
        'conditions_hash',
        'status',
        'resource_id',
        'limit_of_usages',
        'number_of_usages',
        'user_conditions_hash'
    ];

    protected $rules = [
        'incentive_id'    => [
            'checks'   => [
                'integer',
            ],
            'messages' => [
                '*' => ['default_text' => 'Incentive ID is not Integer!'],
            ],
        ],
        'start_date'      => [
            'checks'   => [
                'date',
                'nullable',
                'required_without:incentive_id'
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be a date!',
                ],
            ],
        ],
        'end_date'        => [
            'checks'   => [
                'date',
                'nullable',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be a date!',
                ],
            ],
        ],
        'priority'        => [
            'checks'   => [
                'integer',
            ],
            'messages' => [
                '*' => ['default_text' => 'Incentive ID is not Integer!'],
            ],
        ],
        'stop'            => [
            'checks'   => [
                'boolean',
            ],
            'messages' => [
                '*' => [
                    'default_text' => 'Stop Sign must be 1 or 0 !',
                ],
            ],
        ],
        'incentive_type'  => [
            'checks'   => [
                'string',
                'required',
                'sometimes'
            ],
            'messages' => [
                '*' => ['default_text' => 'Incentive Type is not set!'],
            ],
        ],
        'conditions_hash' => [
            'checks'   => [
                'string',
                'max:1500',

            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute name must be a string and less than 1500 characters!',
                ],
            ],
        ],
        'status'          => [
            'checks'   => [
                'boolean',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be 1 or 0 !',
                ],
            ],
        ],

        'resource_id' => [
            'checks'   => [
                'integer',
                'nullable'
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer!',
                ],
            ],
        ],

        'limit_of_usages'       => [
            'checks'   => [
                'integer',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer!',
                ],
            ],
        ],
        'number_of_usages'      => [
            'checks'   => [
                'integer',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is not integer!',
                ],
            ],
        ],
        'users_conditions_hash' => [
            'checks'   => [
                'string',
                'max:1500',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute name must be a string and less than 1500 characters!',
                ],
            ],
        ],
    ];

    /**
     * @return HasOne
     */
    public function description()
    {
        return $this->hasOne(IncentiveDescription::class, 'incentive_id', 'incentive_id')
            ->where('language_id', '=', static::$current_language_id);
    }

    /**
     * @return HasMany
     */
    public function descriptions()
    {
        return $this->hasMany(IncentiveDescription::class, 'incentive_id');
    }

    /**
     * @param array $data
     * @return false|int
     * @throws InvalidArgumentException
     */
    public static function addIncentive(array $data = [])
    {
        $data['start_date'] = $data['start_date']
            ? Carbon::parse($data['start_date'])->startOfDay()->toDateTimeString()
            : null;

        $data['end_date'] = $data['end_date']
            ? Carbon::parse($data['end_date'])->endOfDay()->toDateTimeString()
            : null;

        $data['incentive_type'] = $data['incentive_type'] ?? 'global';

        Registry::db()->beginTransaction();
        try {
            $incentive = new Incentive($data);
            $incentive->save();
            $incentiveId = $incentive->incentive_id;

            $language = Registry::language();

            $language->replaceDescriptions(
                'incentive_descriptions',
                ['incentive_id' => $incentiveId],
                [
                    $language->getContentLanguageID() =>
                        [
                            'name'              => $data['name'],
                            'description'       => $data['description'],
                            'description_short' => $data['description_short'],
                        ],
                ]
            );
            Registry::db()->commit();
            Registry::cache()->flush('incentive');
            return $incentiveId;
        } catch (\Exception $e) {
            Registry::log()->error($e->getMessage());
            Registry::db()->rollback();
            return false;
        }
    }

    /**
     * @param int $incentive_id
     * @param array $data
     * @return bool
     * @throws InvalidArgumentException
     */
    public static function editIncentive(int $incentive_id, array $data)
    {

        $language = Registry::language();


        if (isset($data['start_date'])) {
            $data['start_date'] = $data['start_date']
                ? Carbon::parse($data['start_date'])->startOfDay()->toDateTimeString()
                : null;
        }

        if (isset($data['end_date'])) {
            $data['end_date'] = $data['end_date']
                ? Carbon::parse($data['end_date'])->endOfDay()->toDateTimeString()
                : null;
        }
        Registry::db()->beginTransaction();
        try {
            $incentive = static::find($incentive_id);

            if (!$incentive) {
                throw new \Exception(__FUNCTION__ . ': Incentive #' . $incentive_id . ' not found');
            }

            $incentive->update($data);

            $bd = new IncentiveDescription();
            $fillable = $bd->getFillable();

            $update = [];
            foreach ($fillable as $field_name) {
                if (isset($data[$field_name])) {
                    $update[$field_name] = $data[$field_name];
                }
            }

            if (count($update)) {
                $language->replaceDescriptions('incentive_descriptions',
                    ['incentive_id' => $incentive_id],
                    [$language->getContentLanguageID() => $update]);
            }
            Registry::db()->commit();
        } catch (\Exception $e) {
            Registry::db()->rollback();
            Registry::log()->error($e->getMessage());
            return false;
        }

        Registry::cache()->flush('incentive');
        return true;
    }

    /**
     * @param array $params - array(
     * 'language_id' => language of incentive descriptions
     * 'start'
     * 'sort'
     * 'order'
     * 'limit'
     * 'filter' => array(
     * 'only_active' => true - sign to select only currently active incentives,
     * with valid daterange and active status
     * 'keyword' => any word for search by name, descriptions
     * 'include' => array with incentive IDs
     * 'exclude' => array with incentive IDs
     * )
     *
     *
     * )
     * @return \Illuminate\Support\Collection
     */
    public static function getIncentives(array $params)
    {
        $params['language_id'] = $params['language_id'] ?: static::$current_language_id;
        $params['sort'] = $params['sort'] ?? 'incentives.sort_order';
        $params['order'] = $params['order'] ?? 'ASC';
        $params['start'] = max($params['start'], 0);
        $params['limit'] = abs((int)$params['limit']) ?: 20;

        $filter = (array)$params['filter'];

        $filter['include'] = $filter['include'] ?? [];
        $filter['exclude'] = $filter['exclude'] ?? [];

        //override to use prepared version of filter inside hooks
        $params['filter'] = $filter;

        $db = Registry::db();

        $b_table = $db->table_name('incentives');
        $bd_table = $db->table_name('incentive_descriptions');

        $query = self::selectRaw(Registry::db()->raw_sql_row_count() . ' ' . $bd_table . '.*');
        $query->addSelect('incentives.*');
        $query->leftJoin(
            'incentive_descriptions',
            function ($join) use ($params) {
                /** @var JoinClause $join */
                $join->on('incentive_descriptions.incentive_id', '=', 'incentives.incentive_id')
                    ->where('incentive_descriptions.language_id', '=', $params['language_id']);
            }
        );

        // show active incentives for sf-side. For admin - returns all
        if (ABC::env('IS_ADMIN') !== true || $filter['only_active']) {
            if ($filter['date']) {
                if ($filter['date'] instanceof Carbon) {
                    $now = $filter['date']->toDateTimeString();
                } else {
                    $now = Carbon::parse($filter['date'])->toDateTimeString();
                }
            } else {
                $now = Carbon::now()->toDateTimeString();
            }

            $query->whereRaw("COALESCE(" . $db->table_name('incentives') . ".start_date, NOW()) <= '" . $now . "'")
                ->whereRaw("COALESCE(" . $db->table_name('incentives') . ".end_date, NOW()) >= '" . $now . "'")
                ->active('incentives');
        }

        if ((array)$filter['include']) {
            $query->whereIn('incentives.incentive_id', (array)$filter['include']);
        }
        if ((array)$filter['exclude']) {
            $query->whereNotIn('incentives.incentive_id', (array)$filter['exclude']);
        }

        if ($filter['keyword']) {
            $query->where(
                function ($subQuery) use ($params, $db) {
                    $keyWord = $db->escape(mb_strtolower($params['filter']['keyword']));

                    $subQuery->orWhere('incentive_descriptions.name', 'like', '%' . $keyWord . '%');
                    $subQuery->orWhere('incentive_descriptions.description', 'like', '%' . $keyWord . '%');
                    $subQuery->orWhere('incentive_descriptions.description_short', 'like', '%' . $keyWord . '%');

                    //allow to extend search criteria
                    $hookParams = $params;
                    $hookParams['subquery_keyword'] = true;
                    Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $subQuery, $hookParams);
                }
            );
        }

        //NOTE: order by must be raw sql string
        $sort_data = [
            'incentive_id'   => $b_table . ".incentive_id",
            'name'           => "LCASE(" . $bd_table . ".name)",
            'sort_order'     => $b_table . ".sort_order",
            'priority'       => $b_table . ".priority",
            'status'         => $b_table . ".status",
            'date_modified'  => $b_table . ".date_modified",
            'incentive_type' => $b_table . ".incentive_type"
        ];

        $orderBy = $sort_data[$params['sort']] ?: 'name';
        if (isset($params['order']) && (strtoupper($params['order']) == 'DESC')) {
            $sorting = "desc";
        } else {
            $sorting = "asc";
        }

        // for SF sort by incentive group first
        if (ABC::env('IS_ADMIN') !== true) {
            $query->orderByRaw($sort_data['incentive_type']);
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
        $cd = new IncentiveDescription();
        $casts = $cd->getCasts();
        $output->total = $totalNumRows;
        foreach ($output as &$item) {
            foreach ($item->attributes as $name => &$value) {
                if (isset($casts[$name]) && class_exists($casts[$name])) {
                    /** @var Html|Json $castable */
                    $castable = new $casts[$name];
                    if ($castable instanceof Castable) {
                        $value = $castable->get($cd, $name, $value, []);
                    }
                }
            }
            $item->total_num_rows = $totalNumRows;
        }

        return $output;
    }

    public static function getIncentive(int $incentiveId)
    {
        return Incentive::getIncentives(
            [
                'filter' => [
                    'include' => [$incentiveId]
                ]
            ]
        )?->first();
    }


    public function delete()
    {
        IncentiveDescription::where('incentive_id', '=', $this->getKey())?->delete();
        return parent::delete();
    }

    /**
     * @param CheckoutBase $checkout
     * @param array|null $params
     * @return array|null
     * @throws \Exception
     */

    public static function getCustomerIncentives(CheckoutBase $checkout, ?array $params = [])
    {
        $output = [];
        $aCustomer = $checkout->getCustomer();

        if (!$aCustomer || !$aCustomer->isLogged()) {
            return [];
        }
        $promo = ABC::getObjectByAlias('APromotion');
        if (!$promo) {
            throw new \Exception('APromotion class not found in the environment');
        }


        $activeIncentives = static::getIncentives(['sort' => 'priority', 'order' => 'desc']);

        foreach ($activeIncentives as $incentive) {
            //skip incomplete
            if (!$incentive->conditions['conditions'] || !$incentive->bonuses) {
                continue;
            }
            //check already applied bonuses
            if ((int)$incentive->limit_of_usages > 0) {
                $applied = IncentiveApplied::where('incentive_id', '=', $incentive->incentive_id)
                    ->where('customer_id', '=', $aCustomer->getId())
                    ->where('result', '=', 1)
                    ->count();
                if ($applied >= $incentive->limit_of_usages) {
                    continue;
                }
            }
            $ruleCount = 0;
            $checkResults = ['rule_counts' => ['true' => 0, 'false' => 0]];
            foreach ($incentive->conditions['conditions'] as $condKey => $conditionList) {
                /** @var false|BaseIncentiveCondition $conditionObj */
                $conditionObj = $promo->getConditionObjectByKey($condKey);
                if (!$conditionObj) {
                    continue;
                }

                foreach ($conditionList as $ruleSet) {
                    $ruleCount++;
                    if ($conditionObj->getRelatedTo() == 'checkout') {
                        //think all conditions related to check out
                        // process probably available for customer
                        $checkResults['rule_counts']['true'] += 1;
                    } elseif ($conditionObj->getRelatedTo() == 'customer') {
                        /** @var CustomerCountry $conditionObj */
                        $res = $conditionObj->check($checkout, $ruleSet);

                        if ($res) {
                            $checkResults['rule_counts']['true'] += 1;
                        } else {
                            $checkResults['rule_counts']['false'] += 1;
                        }
                    }
                }
            }
            //check relations between rules inside condition
            if (
                ($incentive->conditions['relation']['if'] === 'all'
                    && $incentive->conditions['relation']['value'] === 'true'
                    && $checkResults['rule_counts']['true'] == $ruleCount)
                ||
                ($incentive->conditions['relation']['if'] === 'all'
                    && $incentive->conditions['relation']['value'] === 'false'
                    && $checkResults['rule_counts']['false'] == $ruleCount)
                ||
                ($incentive->conditions['relation']['if'] === 'any'
                    && $incentive->conditions['relation']['value'] === 'true'
                    && $checkResults['rule_counts']['true'] > 0)
                ||
                ($incentive->conditions['relation']['if'] === 'any'
                    && $incentive->conditions['relation']['value'] === 'false'
                    && $checkResults['rule_counts']['false'] > 0)
            ) {
                unset($incentive->total_num_rows);
                $output[] = $incentive?->toArray();
            }
        }

        return $output;
    }
}