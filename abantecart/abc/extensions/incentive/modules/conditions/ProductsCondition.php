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

use abc\core\engine\AResource;
use abc\core\engine\Registry;
use abc\core\lib\AException;
use abc\core\lib\BaseIncentiveCondition;
use abc\core\lib\CheckoutBase;
use abc\core\lib\contracts\IncentiveConditionInterface;
use abc\core\view\AView;
use abc\extensions\incentive\modules\traits\ConditionTrait;
use abc\models\catalog\Product;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;

class ProductsCondition extends BaseIncentiveCondition implements IncentiveConditionInterface
{
    use ConditionTrait;

    protected string $relatedTo = 'checkout';
    /** @var string can be "storefront" or "background" or "both" */
    protected string $section = 'storefront';
    protected string $key = 'products';
    /** @var string */
    public const defaultAdminTpl = 'responses/conditions/default.tpl';

    public function getName($languageId = null): string
    {
        return Registry::language()->t('incentive_condition_' . $this->key . '_name', 'Products');
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
        if ($params && isset($params['value']) && is_array($params['value'])) {
            $results = Product::getProducts(
                [
                    'filter' => [
                        'include'      => $params['value'],
                        'only_enabled' => true
                    ]
                ]
            );
            if ($results) {
                $resource = new AResource('image');
                $thumbnails = $resource->getMainThumbList(
                    'products',
                    $results->pluck('product_id')?->toArray(),
                    (int)Registry::config()->get('config_image_grid_width'),
                    (int)Registry::config()->get('config_image_grid_height'),
                    true,
                    array_column($results->toArray(), 'name', 'product_id')
                );

                foreach ($results as $r) {
                    $product_id = $r['product_id'];
                    $options[$product_id]['name'] = $r->name . " " . ($r->model ? '(' . $r->model . ')' : '');
                    $options[$product_id]['image'] = $thumbnails[$product_id]['thumb_html'] . ' ' . $options[$product_id]['name'];
                }
            }
        }

        $this->data['fields']['operator'] = $this->getInNotInField($idx, $params['operator'] ?? []);

        $this->data['fields']['value'] = $html->buildElement(
            [
                'type'        => 'multiselectbox',
                'name'        => 'conditions[conditions][' . $this->key . '][' . $idx . '][value][]',
                'value'       => !$params ? '' : $params['value'],
                'options'     => $options,
                'style'       => 'chosen',
                'ajax_url'    => $html->getSecureURL('r/product/product/products'),
                'placeholder' => $language->get('text_select_from_lookup'),
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
     * @throws InvalidArgumentException|ReflectionException|AException
     */
    public function check(CheckoutBase|array $checkout, ?array $params = []): bool
    {
        $products = [];
        if (is_array($checkout)) {
            $value = $checkout['value'];
        } else {
            $products = (array)$checkout->getCart()?->getProducts();
            $value = array_unique(array_column($products, 'product_id'));
        }

        $result = $this->runComparison($value, $params['value'], $params['operator']);

        if ($result) {
            if ($params['operator'] == 'in') {
                $matchedIds = array_intersect($value, $params['value']);
            } else {
                $matchedIds = array_diff($value, $params['value']);
            }

            if (!$products) {
                $this->data['matchedItems'] = $matchedIds;
            } else {
                foreach ($products as $cartProduct) {
                    if (in_array($cartProduct['product_id'], $matchedIds)) {
                        $this->data['matchedItems'] = $cartProduct;
                    }
                }
            }
        }
        return $result;
    }
}