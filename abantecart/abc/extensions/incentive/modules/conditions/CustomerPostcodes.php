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
use abc\models\customer\Address;

class CustomerPostcodes extends BaseIncentiveCondition implements IncentiveConditionInterface
{
    protected string $relatedTo = 'customer';
    /** @var string can be "storefront" or "background" or "both" */
    protected string $section = 'both';
    protected string $key = 'customer_postcodes';

    public const valueValidationPattern = "/[0-9A-z\s\-]/";

    /** @var string */
    public const defaultAdminTpl = 'responses/conditions/default.tpl';

    public function getName($languageId = null): string
    {
        return Registry::language()->t('incentive_condition_' . $this->key . '_name', 'Customer Postcodes');
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
                    'ctn'   => $language->get('text_contain'),
                    'nctn'  => $language->get('text_not_contain'),
                    'in'    => $language->get('text_in_comma'),
                    'notin' => $language->get('text_not_in_comma'),
                ],
                'value'   => $params['operator'] ?? [],
            ]
        );

        $this->data['fields']['value'] = $html->buildElement(
            [
                'type'        => 'input',
                'name'        => 'conditions[conditions][' . $this->key . '][' . $idx . '][value]',
                'value'       => (is_array($params['value']) ? implode(', ', $params['value']) : $params['value']),
                'style'       => 'medium-field',
                'placeholder' => $language->t('text_comma_separated_values', 'Please paste comma separated values')
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
            $value = $checkout['value'] ?: $checkout['postcode'];
        } else {
            $address = $checkout->getShippingAddress() ?: $checkout->getPaymentAddress();
            if (!$address) {
                $addressId = $checkout->getCustomer()?->getAddressId();
                if ($addressId) {
                    $address = Address::find($addressId);
                }
            }
            $value = $address['postcode'];
        }

        if (in_array($params['operator'], ['in', 'notin'])) {
            $params['value'] = is_string($params['value']) ? explode(',', $params['value']) : $params['value'];
        }

        $result = $this->runComparison($value, $params['value'], $params['operator']);

        if ($result) {
            $this->data['matchedItems'] = $value;
        }
        return $result;
    }
}