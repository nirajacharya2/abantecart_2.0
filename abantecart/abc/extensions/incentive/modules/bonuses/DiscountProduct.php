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

use abc\core\engine\AResource;
use abc\core\engine\Registry;
use abc\core\lib\AException;
use abc\core\lib\BaseIncentiveBonus;
use abc\core\lib\CheckoutBase;
use abc\core\lib\contracts\IncentiveBonusInterface;
use abc\core\view\AView;
use abc\models\catalog\Product;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;

class DiscountProduct extends BaseIncentiveBonus implements IncentiveBonusInterface
{
    protected string $key = 'discount_product';
    /** @var string can be "storefront" or "background" or "both" */
    protected string $section = 'storefront';
    /** @var string */
    public const defaultAdminTpl = 'responses/bonuses/default.tpl';

    public function getName($languageId = null): string
    {
        return Registry::language()->t('incentive_bonus_' . $this->key . '_name', 'Discount Product');
    }

    public function renderSubForm(array $inData, ?string $adminTpl = ''): array
    {
        $adminTpl = $adminTpl ?: self::defaultAdminTpl;
        $language = Registry::language();
        $html = Registry::html();
        $view = new AView(Registry::getInstance(), 0);

        $params = $inData['params'];

        $idx = $this->getKey();

        $this->data['label'] = $language->get('entry_discount_products');

        $this->data['fields']['operator'] = $html->buildElement(
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

        $this->data['fields']['value'] = $html->buildElement(
            [
                'type'  => 'number',
                'name'  => 'bonuses[' . $idx . '][value]',
                'value' => $params['value'] ?: 1.00,
                'style' => 'small-field',
                'attr'  => ' min="0.01" step="0.01" '
            ]
        );

        $options = $ids = [];
        if (is_array($params['products'])) {
            $ids = (array)$params['products']['product_id'];
            $quantities = (array)$params['products']['quantity'];
            $results = Product::getProducts(
                [
                    'filter' => [
                        'include' => $ids
                    ]
                ]
            );
            if ($results) {
                $resource = new AResource('image');
                $thumbnails = $resource->getMainThumbList(
                    'products',
                    $ids,
                    (int)Registry::config()->get('config_image_grid_width'),
                    (int)Registry::config()->get('config_image_grid_height'),
                    true,
                    $results->pluck('name', 'product_id')->toArray()
                );
                foreach ($results as $r) {
                    $productId = $r['product_id'];
                    $options[$productId]['name'] = $r->name . " " . ($r->model ? '(' . $r->model . ')' : '');
                    $options[$productId]['image'] = $thumbnails[$productId]['thumb_html']
                        . '&nbsp;'
                        . $options[$productId]['name']
                        . trim(
                            $html->buildElement(
                                [
                                    'type'        => 'number',
                                    'name'        => 'bonuses[' . $idx . '][products][quantity][' . $productId . ']',
                                    'value'       => (int)$quantities[$productId],
                                    'style'       => 'small-field',
                                    'placeholder' => 'Quantity',
                                ]
                            )
                        );
                }
            }
        }

        $this->data['fields']['product'] = $html->buildElement(
            [
                'type'        => 'multiselectbox',
                'name'        => 'bonuses[' . $idx . '][products][product_id][]',
                'value'       => $ids,
                'options'     => $options,
                'style'       => 'chosen',
                'ajax_url'    => $html->getSecureURL('r/listing_grid/incentive/discount_products', '&idx=' . $idx),
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
     * @param CheckoutBase|array|null $checkout
     * @param array|null $params
     * @return float|int
     * @throws AException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public function getBonus(CheckoutBase|array|null $checkout, ?array $params): float|int
    {
        $discount = 0.0;
        $products = $checkout->getCart()->getProducts();
        foreach ($products as $product) {
            $pId = $product['product_id'];
            $qnty = $params['products']['quantity'][$pId];
            if (in_array($pId, $params['products']['product_id']) && $product['quantity'] >= $qnty) {
                $total = $product['price'] * $qnty;
                $discount += $this->calculateDiscount($params['operator'], $total, $params['value']);
            }
        }
        return $discount;
    }
}