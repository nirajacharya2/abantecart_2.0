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
use abc\core\lib\AException;
use abc\core\lib\BaseIncentiveCondition;
use abc\core\lib\CheckoutBase;
use abc\core\lib\contracts\IncentiveConditionInterface;
use abc\core\view\AView;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;

class CartWeight extends BaseIncentiveCondition implements IncentiveConditionInterface
{
    protected string $relatedTo = 'checkout';
    /** @var string can be "storefront" or "background" or "both" */
    protected string $section = 'storefront';
    protected string $key = 'cart_weight';

    public const valueValidationPattern = "/[0-9.]/";
    public const defaultAdminTpl = 'responses/conditions/default.tpl';

    public function getName($languageId = null): string
    {
        return Registry::language()->t('incentive_condition_' . $this->key . '_name', 'Cart Weight');
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
                'name'    => 'conditions[conditions][' . $this->getKey() . '][' . $idx . '][operator]',
                'options' => [
                    'eq'   => $language->get('text_equal'),
                    'neq'  => $language->get('text_not_equal'),
                    'eqlt' => $language->get('text_equal_or_less'),
                    'eqgt' => $language->get('text_equal_or_greater'),
                    'lt'   => $language->get('text_less'),
                    'gt'   => $language->get('text_greater')
                ],
                'value'   => $params['operator'] ?? [],
            ]
        );

        $this->data['fields']['value'] = $html->buildElement(
            [
                'type'  => 'number',
                'name'  => 'conditions[conditions][' . $this->getKey() . '][' . $idx . '][value]',
                'value' => (is_array($params['value']) ? implode(', ', $params['value']) : $params['value']),
                'style' => 'medium-field',
                'attr'  => 'step="0.01"'
            ]
        );
        $this->data['fields']['units'] = '(' . Registry::config()->get('config_weight_class') . ')';

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
     * @throws AException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public function check(CheckoutBase|array $checkout, ?array $params = []): bool
    {
        if (is_array($checkout)) {
            $value = $checkout['value'];
        } else {
            $value = round($checkout->getCart()->getWeight(), 2);
            if (!in_array($params['operator'], ['in', 'notin'])) {
                $params['value'] = round((float)$params['value'], 2);
            } else {
                $params['value'] = array_map(
                    function ($val) {
                        return round((float)$val, 2);
                    },
                    explode(',', $params['value'])
                );
            }
        }
        $result = $this->runComparison($value, $params['value'], $params['operator']);
        if ($result) {
            $this->data['matchedItems'] = $value;
        }
        return $result;
    }
}