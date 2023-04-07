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

class OrderSubtotalCondition extends BaseIncentiveCondition implements IncentiveConditionInterface
{
    protected string $relatedTo = 'checkout';
    /** @var string can be "storefront" or "background" or "both" */
    protected string $section = 'storefront';
    protected string $key = 'order_subtotal';
    public const valueValidationPattern = "/[0-9.]/";
    public const defaultAdminTpl = 'responses/conditions/default.tpl';

    public function getName($languageId = null): string
    {
        return Registry::language()->t('incentive_condition_' . $this->key . '_name', 'Order Subtotal');
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
                'type'        => 'number',
                'name'        => 'conditions[conditions][' . $this->key . '][' . $idx . '][value]',
                'value'       => (float)$params['value'] ?: 1.00,
                'style'       => 'medium-field',
                'placeholder' => Registry::config()->get('config_currency'),
                'attr'        => ' min="0.01" step="0.01" '
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
     * @throws AException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public function check(CheckoutBase|array $checkout, ?array $params = []): bool
    {
        $value = is_array($checkout)
            ? $checkout['value']
            : $checkout->getCart()?->getSubTotal();

        if (!in_array($params['operator'], ['in', 'notin'])) {
            $params['value'] = (float)$params['value'];
        } else {
            $params['value'] = array_map('floatval', explode(',', $params['value']));
        }
        $result = $this->runComparison($value, $params['value'], $params['operator']);
        if ($result) {
            $this->data['matchedItems'] = $value;
        }
        return $result;
    }
}