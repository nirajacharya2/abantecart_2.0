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

use abc\core\ABC;
use abc\core\engine\Registry;
use abc\core\lib\BaseIncentiveCondition;
use abc\core\lib\CheckoutBase;
use abc\core\lib\contracts\IncentiveConditionInterface;
use abc\core\view\AView;
use abc\extensions\incentive\modules\traits\ConditionTrait;
use abc\models\customer\Address;
use abc\models\locale\Country;

class CustomerCountry extends BaseIncentiveCondition implements IncentiveConditionInterface
{
    use ConditionTrait;

    protected string $relatedTo = 'customer';
    /** @var string can be "storefront" or "background" or "both" */
    protected string $section = 'both';
    protected string $key = 'customer_country';
    public const defaultAdminTpl = 'responses/conditions/default.tpl';

    public function getName($languageId = null): string
    {
        return Registry::language()->t('incentive_condition_' . $this->key . '_name', 'Customer Country');
    }

    public function renderSubForm(array $inData, ?string $adminTpl = ''): array
    {
        $adminTpl = $adminTpl ?: self::defaultAdminTpl;
        $language = Registry::language();
        $html = Registry::html();
        $view = new AView(Registry::getInstance(), 0);

        $params = $inData['params'];
        $idx = $inData['idx'];
        $options = [];

        $this->data['fields']['operator'] = $this->getInNotInField($idx, $params['operator'] ?? []);

        $countries = Country::with('description')->active()->get();
        foreach ($countries as $r) {
            $name = htmlentities($r->description->name . '(' . $r->iso_code_3 . ')', ENT_QUOTES, ABC::env('APP_CHARSET'));
            $options[$r['country_id']] = [
                'image' => $name,
                'id'    => $r->iso_code_3,
                'name'  => $r->iso_code_3,
                'price' => '',
                'meta'  => ''
            ];
        }
        $this->data['fields']['value'] = $html->buildElement(
            [
                'type'    => 'multiselectbox',
                'name'    => 'conditions[conditions][' . $this->key . '][' . $idx . '][value][]',
                'value'   => !$params ? [] : $params['value'],
                'options' => $options,
                'style'   => 'chosen',
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
            $value = (int)$checkout['value'] ?: (int)$checkout['country_id'];
        } else {
            $address = $checkout->getShippingAddress() ?: $checkout->getPaymentAddress();
            if (!$address) {
                $addressId = $checkout->getCustomer()?->getAddressId();
                if ($addressId) {
                    $address = Address::find($addressId);
                }
            }
            $value = $address['country_id'];
        }
        $result = $this->runComparison($value, $params['value'], $params['operator']);
        if ($result) {
            $this->data['matchedItems'] = $value;
        }
        return $result;
    }
}