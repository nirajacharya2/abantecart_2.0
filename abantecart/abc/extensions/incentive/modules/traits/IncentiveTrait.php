<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2023 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/

namespace abc\extensions\incentive\modules\traits;

use abc\core\ABC;
use abc\core\engine\AResource;
use abc\core\engine\Registry;
use abc\core\lib\AException;
use abc\core\lib\BaseIncentiveCondition;
use abc\extensions\incentive\models\IncentiveApplied;
use abc\models\customer\Address;
use abc\models\customer\Customer;
use abc\models\locale\Country;
use Exception;
use H;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;
use stdClass;

trait IncentiveTrait
{
    // current incentive info that processing in loop of condition array
    private $currentIncentive = [];
    /** @var array list of already applied incentives. Looks like array customer_id => [incentive_ids] */
    private $applied = [];

    /**
     * @param array $conditions
     *
     * @param array $data
     *
     * @return bool
     */
    public function checkConditions($conditions, $data = [])
    {
        if (!is_array($conditions['conditions']) || !$conditions['conditions']) {
            return false;
        }

        foreach ($conditions['conditions'] as $condKey => $items) {
            $relationOperator = $conditions['relation']['if'];
            $relationValue = ($conditions['relation']['value'] === 'true');
            foreach ($items as $idx => $condition) {
                //index of the same rule inside condition.
                // Note: We can have two or more same rules!
                $data['idx'] = $idx;
                $result = $this->checkCondition($condKey, $condition, $data);
                if ($relationOperator == 'any' && $result === $relationValue) {
                    return true;
                } elseif ($relationOperator == 'all' && $result !== $relationValue) {
                    return false;
                }
            }
        }

        // if still not returned
        return !($conditions['relation']['if'] == 'any');
    }

    /**
     * @param $condKey
     * @param array $condition
     *
     * @param $record
     *
     * @return bool|null
     */
    protected function checkCondition($condKey, $condition, $record)
    {
        $record = (array)$record;
        if (!$condKey) {
            return false;
        }
        /** @var $conditionObj */
        $conditionObj = $this->promo->getConditionObjectByKey($condKey);
        if (!$conditionObj) {
            return false;
        }

        $conditionObj->setIncentiveInfo((array)$this->currentIncentive);
        switch ($condKey) {
            case 'import_products':
                $result = $conditionObj->check(['value' => $record['product_code']], $condition);
                break;
            case 'quantity_import_products':
                $result = $conditionObj->check(
                    [
                        'value' => [
                            'product_code' => $record['product_code'],
                            'quantity'     => $record['quantity']
                        ]
                    ],
                    $condition
                );
                break;
            case 'customer_consolidation_status':
                /** @var Customer|stdClass $customer */
                $customer = Customer::find($record['customer_id']);
                $result = $conditionObj->check(['value' => $customer?->consolidation_status], $condition);
                break;
            case 'customer_country':
                //NOTE: record country is UK, BUT needs to be GB by ISO.
                //process both country codes - two and three-letter codes
                $record['country'] = $record['country'] == 'UK' ? 'GB' : $record['country'];
                if (strlen($record['country']) == 2) {
                    /** @var Country $country */
                    $country = Country::where('iso_code_2', '=', $record['country'])->first();
                    $isoCode3 = $country->iso_code_3;
                } else {
                    /** @var Customer|stdClass $customer */
                    $customer = Customer::find($record['customer_id']);
                    if ($customer) {
                        $address = Address::find($customer->address_id);
                        $isoCode3 = $address->country_id;
                    } else {
                        $isoCode3 = $record['country'];
                    }
                }
                $checkout = array_merge($record, ['value' => $isoCode3]);
                $result = $conditionObj->check($checkout, $condition);
                break;
            case 'account_codes':
                $result = $conditionObj->check(['value' => $record['account_code']], $condition);
                break;
            case 'depot_list':
                $result = $conditionObj->check(['value' => $record['depo']], $condition);
                break;
            default:
                $result = $conditionObj->check((array)$record, $condition);
                break;
        }

        if ($result) {
            //key if as "text key" of condition + index in the rule set
            $this->matchedConditions[$condKey . ':' . $record['idx']] = $conditionObj;
        }
        return $result;
    }

    /**
     * @param int $incentiveId
     * @param int $customerId
     * @param int $resultCode
     * @param float|int $bonusAmount
     * @param mixed $resultData
     *
     * @return bool
     */
    protected function saveResult(int $incentiveId, int $customerId, int $resultCode, float|int $bonusAmount, ?array $resultData)
    {
        if (!$incentiveId || !$customerId || !in_array($resultCode, [0, 1])) {
            Registry::log()->warning('Incentive: saveResult Skipped');
            return false;
        }

        $matchedConditions = [];
        foreach ($this->matchedConditions as $idx => $mCondObj) {
            /** @var BaseIncentiveCondition $mCondObj */
            $matchedConditions[$idx] = $mCondObj->matchedItems() ?? [];
        }

        $resultData['matched_conditions'] = (array)$resultData['matched_conditions'] ?: [];
        if ($matchedConditions) {
            $resultData['matched_conditions'] = $matchedConditions;
        }

        try {
            IncentiveApplied::create(
                [
                    'incentive_id' => $incentiveId,
                    'customer_id'  => $customerId,
                    'result_code'  => $resultCode,
                    'result'       => $resultData,
                    'bonus_amount' => $bonusAmount
                ]
            );

            if ($resultCode == 0) {
                $this->applied[$customerId][] = $incentiveId;
            }
        } catch (Exception $e) {
            Registry::log()->error(__FUNCTION__ . ': ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return false;
        }
        return true;
    }

    /**
     * @param array $incentive
     * @return void
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws AException
     */
    protected function getIncentiveResource(array &$incentive): void
    {
        if ($incentive['resource_id']) {
            $aResource = new AResource('image');
            $incentive['resource'] = $aResource->getResource($incentive['resource_id']);
            $resourceRelPath = $incentive['resource']['type_name'] . '/' . $incentive['resource']['resource_path'];
            if (is_file(ABC::env('DIR_RESOURCES') . $resourceRelPath)) {
                $resourceAbsPath = ABC::env('DIR_RESOURCES') . $resourceRelPath;
                //get logo image dimensions
                $info = H::get_image_size($resourceAbsPath);
                $incentive['resource']['width'] = $info['width'];
                $incentive['resource']['height'] = $info['height'];
                $incentive['resource']['url'] = $this->html->getHomeURL() . 'resources/' . $resourceRelPath;
            }
        }
    }

    /**
     * @param array $incentive
     * @return array
     * @throws AException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    protected function mapIncentiveDataToApiResponse(array $incentive): array
    {
        $this->getIncentiveResource($incentive);
        $result = [
            "id"          => $incentive['incentive_id'],
            "title"       => $incentive['name'],
            "description" => $incentive['description'],
            "image_url"   => $incentive['resource_id'] ? $incentive['resource']['url'] : null,
            "date_start"  => $incentive['start_date'],
            "date_end"    => $incentive['end_date'],
        ];
        if (is_array($incentive['description'])) {
            $result['title'] = $incentive['description']['name'];
            $result['description'] = $incentive['description']['description'];
        }
        return $result;
    }
}