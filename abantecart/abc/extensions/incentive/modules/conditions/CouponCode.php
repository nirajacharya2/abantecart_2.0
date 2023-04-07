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
use abc\models\order\Coupon;
use H;

class CouponCode extends BaseIncentiveCondition implements IncentiveConditionInterface
{
    use ConditionTrait;

    protected string $relatedTo = 'checkout';
    /** @var string can be "storefront" or "background" or "both" */
    protected string $section = 'storefront';
    protected string $key = 'coupon_code';
    /** @var string */
    public const defaultAdminTpl = 'responses/conditions/default.tpl';

    public function getName($languageId = null): string
    {
        return Registry::language()->t('incentive_condition_' . $this->key . '_name', 'Coupon Code');
    }

    public function renderSubForm(array $inData, ?string $adminTpl = ''): array
    {
        $adminTpl = $adminTpl ?: self::defaultAdminTpl;
        $language = Registry::language();
        $html = Registry::html();
        $view = new AView(Registry::getInstance(), 0);

        $params = $inData['params'];
        $idx = $inData['idx'];


        $results = Coupon::with('description')->active()->get();
        $options = [];
        $now = time();
        foreach ($results as $r) {
            // check date range
            if (H::dateISO2Int($r->date_start) > $now || H::dateISO2Int($r->date_end) < $now) {
                continue;
            }
            $options[$r->code] = $r->description->name . ' (' . $r->code . ')';
        }

        if ($options) {
            $this->data['fields']['operator'] = $this->getInNotInField($idx, $params['operator'] ?? []);
            $this->data['fields']['value'] = $html->buildElement(
                [
                    'type'      => 'checkboxgroup',
                    'name'      => 'conditions[conditions][' . $this->getKey() . '][' . $idx . '][value][]',
                    'value'     => !$params ? '' : $params['value'],
                    'options'   => $options,
                    'scrollbox' => true,
                ]
            );
        } else {
            $this->data['fields']['alert'] = Registry::language()->t(
                'incentive_error_no_active_coupons',
                '<i class="fa fa-exclamation-triangle"></i>
                &nbsp;No active coupons found. See 
                <a href="' . $html->getSecureURL('sale/coupon') . '" target="_blank">Sale->Coupons</a> for details'
            );
        }

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
        $value = is_array($checkout)
            ? $checkout['value']
            : ($checkout->getData()['coupon'] ?: Registry::session()->data['coupon']);
        $result = $this->runComparison($value, $params['value'], $params['operator']);
        if ($result) {
            $this->data['matchedItems'] = $value;
        }
        return $result;
    }
}