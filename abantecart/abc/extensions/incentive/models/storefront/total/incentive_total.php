<?php

namespace abc\extensions\incentive\models\storefront\total;

use abc\core\ABC;
use abc\core\engine\Model;
use abc\core\engine\Registry;
use abc\core\lib\APromotion;
use abc\core\lib\BaseIncentiveCondition;
use abc\extensions\incentive\models\Incentive;
use abc\extensions\incentive\models\IncentiveApplied;
use abc\extensions\incentive\modules\traits\IncentiveTrait;
use abc\models\order\OrderDataType;
use abc\models\order\OrderDatum;
use Exception;
use H;
use Illuminate\Support\Collection;

class ModelTotalIncentiveTotal extends Model
{
    use IncentiveTrait;

    /** @var APromotion bool|object */
    protected $promo;
    protected $total;
    protected $total_data;
    /** @var array - list of matched condition instances */
    protected $matchedConditions = [];
    protected $appliedBonuses = [];

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->promo = ABC::getObjectByAlias('APromotion');
    }

    public function getTotal(&$total_data, &$total, &$taxes)
    {
        $status = $this->config->get('incentive_total_status');

        if (!$this->config->get('incentive_cart')
            && in_array($this->request->get['rt'], ['checkout/cart', 'r/checkout/cart/recalc_totals'])
        ) {
            $status = 0;
        }
        if (!$this->config->get('incentive_status')) {
            $status = 0;
        }

        if ($status) {
            $this->total_data = $total_data;
            $this->total = $total;
            $this->apply();
            $total_data = $this->total_data;
            $total = $this->total;
        }
    }

    /**
     * @return Collection
     */
    public function getRules()
    {
        return Incentive::with('description')
            ->active('incentives')
            ->orderBy('incentives.priority')
            ->useCache('incentive')
            ->get();
    }

    protected function apply()
    {
        $applied_incentives = [];
        $order_id = (int)$this->session->data['order_id'];
        $incentives = $this->getRules();
        $customer_id = $this->customer->getId();
        /** @var OrderDataType $result */
        $result = OrderDataType::select('type_id')
            ->where('name', '=', 'incentive_data')
            ->first();

        $orderDataTypeId = $result->type_id;
        foreach ($incentives as $incentive) {
            if ($incentive->conditions['condition_type'] != 'storefront') {
                continue;
            }
            $start_date = strtotime($incentive->start_date);
            $end_date = !in_array($incentive->end_date, ['', '0000-00-00 00:00:00'])
                ? H::dateISO2Int($incentive->end_date)
                : false;
            $end_date = $end_date === false ? (time() + 86400) : $end_date;
            if (time() < $start_date || time() > $end_date) {
                continue;
            }

            //check only for registered customers
            if ((int)$incentive->limit_of_usages && $customer_id) {
                $already_applied_count = IncentiveApplied::getAppliedCount((int)$incentive->incentive_id, $customer_id);
                if ($already_applied_count > (int)$incentive->limit_of_usages) {
                    continue;
                }
            }
            $this->currentIncentive = $incentive->toArray();
            $result = $this->checkConditions($incentive->conditions);
            if ($result) {
                $res = $this->applyBonuses($incentive->description->name, $incentive->bonuses);
                if ($res) {
                    $applied_incentives[$incentive->incentive_id] = $res;
                    if ($incentive->stop) {
                        break;
                    }
                }
            }
        }

        if ($order_id && $applied_incentives && $orderDataTypeId) {
            try {
                $matchedConditions = [];
                foreach ($this->matchedConditions as $idx => $mCondObj) {
                    /** @var BaseIncentiveCondition $mCondObj */
                    $matchedConditions[$idx] = $mCondObj->matchedItems() ?? [];
                }

                OrderDatum::updateOrCreate(
                    [
                        'order_id' => $order_id,
                        'type_id'  => $orderDataTypeId,
                    ],
                    [
                        'data' => [
                            'incentives'         => $applied_incentives,
                            'matched_conditions' => $matchedConditions,
                            'applied_bonuses'    => $this->appliedBonuses
                        ]
                    ]
                );
            } catch (Exception $e) {
                $this->log->error('Incentive Totals Error: ' . $e->getTraceAsString());
            }
        }
    }

    /**
     * @param array $conditionData
     *
     * @return bool|null
     */
    protected function checkCondition($condKey, $conditionData, $data = [])
    {

        if (!$condKey) {
            return null;
        }
        /** @var false|BaseIncentiveCondition $conditionObj */
        $conditionObj = $this->promo->getConditionObjectByKey($condKey);
        if (!$conditionObj || !in_array($conditionObj->getSection(), ['storefront', 'both'])) {
            return null;
        }

        if (!method_exists($conditionObj, 'setIncentiveInfo')) {
            Registry::log()->error(
                'Instance ' . get_class($conditionObj) . ' have not method setIncentiveInfo!'
            );
            return null;
        }

        $conditionObj->setIncentiveInfo((array)$this->currentIncentive);
        $result = $conditionObj->check(Registry::checkout(), $conditionData);
        if ($result) {
            $this->matchedConditions[$condKey . ':' . $data['idx']] = $conditionObj;
        }
        return $result;
    }


    /**
     * @param string $incentives_name
     * @param array $bonuses
     *
     * @return bool|float
     */
    protected function applyBonuses($incentives_name, $bonuses)
    {
        $this->appliedBonuses = [];
        $discount = 0;

        foreach ($bonuses as $bonusKey => $bonus) {
            //prevent duplicate application of bonus
            if (!isset($this->appliedBonuses[$bonusKey])) {
                $bonus_discount = $this->processBonus($bonusKey, $bonus);
                if (($discount + $bonus_discount) > $this->total) {
                    break; // prevent negative total
                }
                $discount += $bonus_discount;
                $this->appliedBonuses[$incentives_name][$bonusKey] = $bonus_discount;
            }
        }

        if (abs($discount)) {
            $currency = $this->currency->getCurrency();
            $decPlace = (int)$currency['decimal_place'] ?: 1;

            if (abs($discount) < "0." . str_repeat('0', ($decPlace - 1)) . '1') {
                $formatted = '';
            } else {
                $formatted = ' - ' . $this->currency->format($discount);
            }

            $this->total_data[] = [
                'id'         => 'incentive',
                'title'      => $formatted ? $incentives_name . ':' : '',
                'text'       => $formatted,
                'value'      => $formatted ? (-$discount) : 0.0,
                'sort_order' => (int)$this->config->get('incentive_total_sort_order'),
                'total_type' => $this->config->get('incentive_total_total_type'),
            ];
            $this->total -= $discount;
            return abs($discount);
        }
        return false;
    }

    /**
     * @param string $bonusKey
     * @param array $bonusParams
     *
     * @return float|int
     */
    protected function processBonus(string $bonusKey, $bonusParams)
    {
        if (!$bonusKey) {
            return 0;
        }
        $bonusParams['value'] = $bonusParams['value'] ?? [];
        $bonusParams['total_data'] = $this->total_data;
        $bonusObj = $this->promo->getBonusObjectByKey($bonusKey);
        if (!$bonusObj || !in_array($bonusObj->getSection(), ['storefront', 'both'])) {
            return 0;
        }
        $bonusObj->setIncentiveInfo((array)$this->currentIncentive);
        $bonusObj->setMatchedConditions(
            (array)$this->matchedConditions
        );
        return $bonusObj->getBonus(Registry::checkout(), $bonusParams);
    }
}
