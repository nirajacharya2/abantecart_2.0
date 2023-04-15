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

class ProductPrice extends BaseIncentiveCondition implements IncentiveConditionInterface
{
    protected string $relatedTo = 'checkout';
    /** @var string can be "storefront" or "background" or "both" */
    protected string $section = 'both';
    protected string $key = 'product_price';
    /** @var string */
    public const defaultAdminTpl = 'responses/conditions/default.tpl';

    public function getName($languageId = null): string
    {
        return Registry::language()->t('incentive_condition_' . $this->key . '_name', 'Product Price');
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
                    'gt'   => $language->get('text_greater'),
                ],
                'value'   => $params['operator'] ?? [],
            ]
        );

        $this->data['fields']['value'] = $html->buildElement(
            [
                'type'  => 'number',
                'name'  => 'conditions[conditions][' . $this->getKey() . '][' . $idx . '][value]',
                'value' => !$params ? '' : $params['value'],
                'style' => 'small-field',
                'attr'  => 'step="0.01"'
            ]
        );
        $this->data['fields']['units'] = '(' . Registry::config()->get('config_currency') . ')';

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
        $result = false;

        $products = is_array($checkout)
            ? $checkout['value']
            : (array)$checkout->getCart()?->getProducts();

        $priceList = array_column((array)$products, 'price', 'product_id');
        $priceList = array_map(function ($v) {
            return round($v, 2);
        }, $priceList);
        $params['value'] = round($params['value'], 2);


        foreach ($products as $product) {
            if ($this->runComparison($product['price'], $params['value'], $params['operator'])) {
                if ($params['operator'] != 'neq') {
                    //equal: when at least one of products have needle price - return true
                    $result = true;
                }
                $this->data['matchedItems'][] = $product;
            }
        }
        if ($params['operator'] == 'neq') {
            $result = !(in_array($params['value'], $priceList));
        }
        return $result;
    }
}