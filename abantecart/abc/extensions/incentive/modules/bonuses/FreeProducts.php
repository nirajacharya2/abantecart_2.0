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
use abc\extensions\incentive\modules\traits\BonusTrait;
use abc\models\catalog\Product;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;

class FreeProducts extends BaseIncentiveBonus implements IncentiveBonusInterface
{
    use BonusTrait;

    protected string $key = 'free_products';
    /** @var string can be "storefront" or "background" or "both" */
    protected string $section = 'storefront';
    /** @var string */
    public const defaultAdminTpl = 'responses/bonuses/default.tpl';

    public function getName($languageId = null): string
    {
        return Registry::language()->t('incentive_bonus_' . $this->key . '_name', 'Free Products');
    }

    public function renderSubForm(array $inData, ?string $adminTpl = ''): array
    {
        $adminTpl = $adminTpl ?: self::defaultAdminTpl;
        $language = Registry::language();
        $html = Registry::html();
        $view = new AView(Registry::getInstance(), 0);

        $params = $inData['params'];
        $idx = $this->getKey();

        $this->data['label'] = $language->get('entry_free_products');

        $options = $ids = [];
        if (is_array($params['products'])) {
            $ids = (array)$params['products']['product_id'];
            $results = Product::getProducts(
                [
                    'filter' => [
                        'include'      => $ids,
                        'only_enabled' => true
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
                    array_column($results->toArray(), 'name', 'product_id')
                );
                foreach ($results as $r) {
                    $product_id = $r['product_id'];
                    $options[$product_id]['name'] = $r->name . " " . ($r->model ? '(' . $r->model . ')' : '');
                    $options[$product_id]['image'] = $thumbnails[$product_id]['thumb_html']
                        . $options[$product_id]['name']
                        . trim(
                            $html->buildInput(
                                [
                                    'name'        => 'bonuses[' . $idx . '][products][quantity][' . $product_id . ']',
                                    'value'       => $params['products']['quantity'][$product_id],
                                    'style'       => 'small-field',
                                    'placeholder' => 'Quantity',
                                ]
                            )
                        );
                }
            }
        }

        $this->data['fields']['value'] = $html->buildElement(
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
                $discount += $this->calculateDiscount('to_fixed', $total, 0);
            }
        }
        return $discount;
    }
}