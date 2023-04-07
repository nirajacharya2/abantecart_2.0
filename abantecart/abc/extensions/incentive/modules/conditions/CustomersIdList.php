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

namespace abc\extensions\incentive\modules\conditions;

use abc\core\engine\Registry;
use abc\core\lib\BaseIncentiveCondition;
use abc\core\lib\CheckoutBase;
use abc\core\lib\contracts\IncentiveConditionInterface;
use abc\core\view\AView;
use abc\extensions\incentive\modules\traits\ConditionTrait;
use abc\models\customer\Customer;

class CustomersIdList extends BaseIncentiveCondition implements IncentiveConditionInterface
{
    use ConditionTrait;

    protected string $relatedTo = 'customer';
    /** @var string can be "storefront" or "background" or "both" */
    protected string $section = 'both';
    protected string $key = 'customer_id_list';
    /** @var string */
    public const defaultAdminTpl = 'responses/conditions/customer_id_list.tpl';

    public function getName($languageId = null): string
    {
        return Registry::language()->t('incentive_condition_' . $this->key . '_name', 'Customer ID List');
    }

    public function renderSubForm(array $inData, ?string $adminTpl = ''): array
    {
        $adminTpl = $adminTpl ?: self::defaultAdminTpl;
        $language = Registry::language();
        $html = Registry::html();
        $view = new AView(Registry::getInstance(), 0);

        $params = $inData['params'];
        $idx = $inData['idx'];

        $this->data['fields']['operator'] = $this->getInNotInField($idx, $params['operator'] ?? []);

        $options = [];
        if (is_array($params['value'])) {
            $customers = Customer::find($params['value']);
            foreach ($customers as $customer) {
                $options[$customer->customer_id] = $customer->firstname . ' ' . $customer->lastname;
            }
        }
        $this->data['fields']['value'] = $html->buildElement(
            [
                'type'        => 'textarea',
                'name'        => 'conditions[conditions][' . $this->getKey() . '][' . $idx . '][value]',
                'value'       => !$params ? '' : $params['value'],
                'placeholder' => $language->t('text_comma_separated_values', 'Please paste comma separated values'),
                'attr'        => ' pattern="/^\d+(,\d+)*$/" required',
            ]
        );

        $view->batchAssign($inData);
        $view->batchAssign($this->data);
        return [
            'label' => $this->getName(),
            'html'  => $view->fetch($adminTpl)
        ];
    }


    /**
     * @param CheckoutBase|array $checkout
     * @param array|null $params
     * @return bool
     */
    public function check(CheckoutBase|array $checkout, ?array $params = []): bool
    {
        if (is_array($checkout)) {
            $value = (int)$checkout['value'] ?: (int)$checkout['customer_id'];
        } else {
            $value = $checkout->getCustomer()?->getId();
        }

        $params['value'] = is_string($params['value']) ? explode(",", $params['value']) : (array)$params['value'];
        $params['value'] = array_map('intval', $params['value']);

        $result = $this->runComparison($value, $params['value'], $params['operator']);
        if ($result) {
            $this->data['matchedItems'] = $value;
        }
        return $result;
    }
}