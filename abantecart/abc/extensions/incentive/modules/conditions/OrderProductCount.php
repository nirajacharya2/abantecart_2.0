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

class OrderProductCount extends BaseIncentiveCondition implements IncentiveConditionInterface
{
    protected string $relatedTo = 'checkout';
    /** @var string can be "storefront" or "background" or "both" */
    protected string $section = 'storefront';
    protected string $key = 'order_product_count';
    /** @var string */
    public const defaultAdminTpl = 'responses/conditions/default.tpl';

    public function getName($languageId = null): string
    {
        return Registry::language()->t('incentive_condition_' . $this->key . '_name', 'Order Product Count');
    }

    public function renderSubForm(array $inData, ?string $adminTpl = ''): array
    {
        $adminTpl = $adminTpl ?: self::defaultAdminTpl;
        $language = Registry::language();
        $html = Registry::html();
        $view = new AView(Registry::getInstance(), 0);

        $params = $inData['params'];
        $idx = $inData['idx'];

        $this->data['fields']['operator'] = $html->buildElement(
            [
                'type'    => 'selectbox',
                'name'    => 'conditions[conditions][' . $this->key . '][' . $idx . '][operator]',
                'options' => [
                    'eq'    => $language->get('text_equal'),
                    'neq'   => $language->get('text_not_equal'),
                    'eqlt'  => $language->get('text_equal_or_less'),
                    'eqgt'  => $language->get('text_equal_or_greater'),
                    'lt'    => $language->get('text_less'),
                    'gt'    => $language->get('text_greater'),
                    'in'    => $language->get('text_in_comma'),
                    'notin' => $language->get('text_not_in_comma'),
                ],
                'value'   => $params['operator'] ?? [],
            ]
        );

        $this->data['fields']['value'] = $html->buildElement(
            [
                'type'  => 'input',
                'name'  => 'conditions[conditions][' . $this->getKey() . '][' . $idx . '][value]',
                'value' => !$params ? '' : $params['value'],
                'style' => 'medium-field',
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
            $value = $checkout['value'];
        } else {
            $value = $checkout->getCart()?->countProducts();
        }

        if (!in_array($params['operator'], ['in', 'notin'])) {
            $params['value'] = (int)$params['value'];
        } else {
            $params['value'] = array_map('intval', explode(',', $params['value']));
        }

        $result = $this->runComparison($value, $params['value'], $params['operator']);
        if ($result) {
            $this->data['matchedItems'] = $value;
        }
        return $result;
    }
}