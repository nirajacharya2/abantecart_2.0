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
use abc\extensions\incentive\modules\traits\ConditionTrait;
use abc\models\catalog\Category;
use abc\models\catalog\Product;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;

class ProductCategories extends BaseIncentiveCondition implements IncentiveConditionInterface
{
    use ConditionTrait;

    protected string $relatedTo = 'checkout';
    /** @var string can be "storefront" or "background" or "both" */
    protected string $section = 'storefront';
    protected string $key = 'product_categories';
    /** @var string */
    public const defaultAdminTpl = 'responses/conditions/default.tpl';

    public function getName($languageId = null): string
    {
        return Registry::language()->t('incentive_condition_' . $this->key . '_name', 'Product Categories');
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

        $results = Category::getCategories(0);
        $options = array_column($results, 'name', 'category_id');

        $this->data['fields']['value'] = $html->buildElement(
            [
                'type'        => 'checkboxgroup',
                'name'        => 'conditions[conditions][' . $this->getKey() . '][' . $idx . '][value][]',
                'value'       => !$params ? '' : $params['value'],
                'options'     => $options,
                'style'       => 'chosen',
                'placeholder' => $language->get('text_select_category'),
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
        $params['value'] = array_filter((array)$params['value']);
        $params['value'] = array_map('intval', $params['value']);

        $products = (array)$checkout->getCart()?->getProducts();
        if (!$products) {
            return false;
        }

        $products = (array)Registry::db()->table('products_to_categories')
            ->{$params['operator'] == 'in' ? 'whereIn' : 'whereNotIn'}(
                'products_to_categories.product_id',
                array_unique(array_column($products, 'product_id'))
            )
            ->leftJoin(
                'products',
                'products_to_categories.product_id',
                '=',
                'products.product_id',
            )->get()
            ?->toArray();
        $value = array_unique(array_column($products, 'category_id'));

        $result = $this->runComparison($value, $params['value'], $params['operator']);
        if ($result) {
            $this->data['matchedItems'] = $products;
        }
        return $result;
    }
}