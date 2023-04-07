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
use abc\models\customer\CustomerGroup;

class CustomerGroupsCondition extends BaseIncentiveCondition implements IncentiveConditionInterface
{
    use ConditionTrait;

    protected string $relatedTo = 'customer';
    /** @var string can be "storefront" or "background" or "both" */
    protected string $section = 'both';
    protected string $key = 'customer_groups';
    /** @var string */
    public const defaultAdminTpl = 'responses/conditions/default.tpl';

    public function getName($languageId = null): string
    {
        return Registry::language()->t('incentive_condition_' . $this->key . '_name', 'Customer Groups');
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
        $results = CustomerGroup::all();
        $defaultCustomerGroup = Registry::config()->get('config_customer_group_id');
        foreach ($results as $r) {
            $options[$r->customer_group_id] = $r->name
                . ($r->customer_group_id == $defaultCustomerGroup ? $language->get('text_default') : null);
        }
        $this->data['fields']['value'] = $html->buildElement(
            [
                'type'      => 'checkboxgroup',
                'name'      => 'conditions[conditions][' . $this->getKey() . '][' . $idx . '][value][]',
                'value'     => !$params ? '' : $params['value'],
                'options'   => $options,
                'scrollbox' => true,
            ]
        );

        $view->batchAssign($this->data);
        $view->batchAssign($inData);
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
            $value = (int)$checkout['value'] ?: (int)$checkout['customer_group_id'];
        } else {
            $value = $checkout->getCustomer()?->getCustomerGroupId();
        }

        $result = $this->runComparison($value, $params['value'], $params['operator']);
        if ($result) {
            $this->data['matchedItems'] = $value;
        }
        return $result;
    }
}