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
use abc\core\lib\BaseIncentiveBonus;
use abc\core\lib\CheckoutBase;
use abc\core\lib\contracts\IncentiveBonusInterface;
use abc\core\view\AView;
use abc\extensions\incentive\modules\traits\BonusTrait;

class FreeShipping extends BaseIncentiveBonus implements IncentiveBonusInterface
{
    use BonusTrait;

    protected string $key = 'free_shipping';
    /** @var string can be "storefront" or "background" or "both" */
    protected string $section = 'storefront';
    /** @var string */
    public const defaultAdminTpl = 'responses/bonuses/default.tpl';

    public function getName($languageId = null): string
    {
        return Registry::language()->t('incentive_bonus_' . $this->key . '_name', 'Free Shipping');
    }

    public function renderSubForm(array $inData, ?string $adminTpl = ''): array
    {
        $adminTpl = $adminTpl ?: self::defaultAdminTpl;
        $html = Registry::html();
        $view = new AView(Registry::getInstance(), 0);

        $params = $inData['params'];
        $idx = $this->getKey();

        $results = Registry::extensions()->getInstalled('shipping');
        $options = [];
        foreach ($results as $r) {
            $options[$r] = Registry::extensions()->getExtensionName($r);
        }

        $this->data['fields']['value'] = $html->buildElement(
            [
                'type'      => 'checkboxgroup',
                'name'      => 'bonuses[' . $idx . '][value][]',
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
     * @param CheckoutBase|array|null $checkout
     * @param array|null $params
     * @return float|int
     */
    public function getBonus(CheckoutBase|array|null $checkout, ?array $params): float|int
    {
        $totalData = (array)$params['total_data'];
        if (!$totalData) {
            return 0;
        }

        $shippingCost = false;
        foreach ($totalData as $item) {
            if ($item['id'] == 'shipping') {
                $shippingKey = $checkout->getShippingKey();
                $shippingKey = explode('.', $shippingKey);
                $shippingKey = $shippingKey[0];
                if (in_array($shippingKey, $params['value'])) {
                    $shippingCost = $item['value'];
                    break;
                }
            }
        }
        if ($shippingCost === false) {
            return 0.0;
        }
        return $shippingCost;
    }
}