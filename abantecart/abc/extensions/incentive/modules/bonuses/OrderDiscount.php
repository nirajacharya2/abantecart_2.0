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

namespace abc\extensions\incentive\modules\bonuses;

use abc\core\engine\Registry;
use abc\core\lib\AException;
use abc\core\lib\BaseIncentiveBonus;
use abc\core\lib\CheckoutBase;
use abc\core\lib\contracts\IncentiveBonusInterface;
use abc\core\view\AView;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;

class OrderDiscount extends BaseIncentiveBonus implements IncentiveBonusInterface
{
    protected string $key = 'order_discount';
    /** @var string can be "storefront" or "background" or "both" */
    protected string $section = 'storefront';
    /** @var string */
    public const defaultAdminTpl = 'responses/bonuses/default.tpl';

    public function getName($languageId = null): string
    {
        return Registry::language()->t('incentive_bonus_' . $this->key . '_name', 'Order Discount');
    }

    public function renderSubForm(array $inData, ?string $adminTpl = ''): array
    {
        $adminTpl = $adminTpl ?: self::defaultAdminTpl;
        $language = Registry::language();
        $html = Registry::html();
        $view = new AView(Registry::getInstance(), 0);

        $params = $inData['params'];
        $idx = $this->getKey();

        $this->data['label'] = $language->get('entry_order_product_weight');
        $this->data['fields']['operator'] = $html->buildSelectBox(
            [
                'type'    => 'selectbox',
                'name'    => 'bonuses[' . $idx . '][operator]',
                'options' => [
                    'by_prc'   => $language->get('text_by_prc'),
                    'to_prc'   => $language->get('text_to_prc'),
                    'by_fixed' => $language->get('text_by_fixed'),
                    'to_fixed' => $language->get('text_to_fixed'),
                ],
                'value'   => $params['operator'] ?? [],
            ]
        );

        $this->data['fields']['value'] = $html->buildInput(
            [
                'type'  => 'input',
                'name'  => 'bonuses[' . $idx . '][value]',
                'value' => !$params ? '' : $params['value'],
                'style' => 'small-field',
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
     * @param CheckoutBase|array|null $checkout
     * @param array|null $params
     * @return float|int
     * @throws AException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public function getBonus(CheckoutBase|array|null $checkout, ?array $params): float|int
    {
        $subtotal = $checkout->getCart()->getSubTotal();
        return $this->calculateDiscount($params['operator'], $subtotal, $params['value']);
    }
}